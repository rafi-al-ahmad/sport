<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductUsage;
use App\Models\UsageStep;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected function productValidationRules($rules = [])
    {
        return array_merge([
            'eyebrow_text' => ['required', 'max:190'],
            'title' => ['required', 'max:190'],
            'code' => ['nullable', 'string',],
            'description' => ['required', 'string'],
            'meta_desc' => ['required', 'string'],
            'status' => ['required', 'in:0,1'],
            'category_id' => ['required', 'exists:categories,id'],
            'language' => ['required', 'string', Rule::in(config('app.supported_locales'))],

            'options' => ['nullable', 'array'],
            'options.*' => ["string"],
            'variants' => ['array', 'required'],
            'variants.*' => ['array'],
            'variants.*.sku' => ['nullable', 'string'],
            'variants.*.quantity' => ['required', 'numeric'],
            'variants.*.price' => ['required', 'numeric'],
            'variants.*.compareAtPrice' => ['nullable', 'numeric'],
            'variants.*.images' => ['nullable', 'array'],
            'variants.*.videos' => ['nullable', 'array'],
            'variants.*.videos.*' => ['string'],
            'variants.*.images.*' => ['nullable', 'array'],
            'variants.*.images.*.image' => ['required', 'imageable'],
            'variants.*.images.*.options' => ['nullable', 'array'],

            'variants.*.options' => [
                'array',
                function ($attribute, $variantOptions, $fail) {
                    $options = request()->get('options'); // Retrieve options from options attribute

                    if (isset($options)) {
                        foreach ($variantOptions as $variantOptionKey => $variantOptionValue) {
                            // insure that variant option is one of product options,
                            if (in_array($variantOptionKey, $options)) {
                                unset($options[array_search($variantOptionKey, $options)]);
                            }
                        }
                        // if product options array not empty thats mean there missing options in variant
                        if (count($options) > 0) {
                            return $fail('All options must be set in variant.');
                        }
                    }
                },
            ],
            'variants.*.options.*.title' => ['required'],
            'variants.*.options.*.value' => ['required'],
        ], $rules);
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Product::query();
        
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->code) {
            $query->where('code', 'like', $request->code . '%');
        }

        if ($request->has("status")) {
            $query->where('status', $request->status);
        }
        
        if ($request->has("social_responsible")) {
            $query->where('is_social_responsible', $request->social_responsible);
        }

        if ($request->key) {
            $key = $request->key;
            $query->where(function ($query) use ($key) {
                $query->orWhere('title', 'like', '%' . $key . '%')
                    ->orWhere('description', 'like', '%' . $key . '%')
                    ->orWhere('meta_desc', 'like', '%' . $key . '%');
            });
        }
        return ProductResource::collection( $query->paginate($request->limit));
    }


    /**
     * Get all active products with their chiledren
     *
     * @return App\Http\Resources\CollectionResource
     */
    public function activeWithFilters(Request $request)
    {
        $query = Product::where('status', 1);

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->code) {
            $query->where('code', 'like', $request->code . '%');
        }
        
        if ($request->has("social_responsible")) {
            $query->where('is_social_responsible', $request->social_responsible);
        }

        if ($request->key) {
            $key = $request->key;
            $query->where(function ($query) use ($key) {
                $query->orWhere('title', 'like', '%' . $key . '%')
                    ->orWhere('description', 'like', '%' . $key . '%')
                    ->orWhere('meta_desc', 'like', '%' . $key . '%');
            });
        }

        return ProductResource::collection($query->paginate($request->limit));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, $this->productValidationRules())->validate();

        $product = new Product();
        $product->setTranslation('eyebrow_text', $data['language'], $data['eyebrow_text']);
        $product->setTranslation('title', $data['language'], $data['title']);
        $product->setTranslation('description', $data['language'], $data['description']);
        $product->setTranslation('meta_desc', $data['language'], $data['meta_desc']);
        $product->code = $data['code'];
        $product->status = $data['status'];
        $product->category_id = $data['category_id'];
        $product->languages = [$data['language']];
        $product->options = [$data['language'] => $data['options']];
        if ($request->social_responsible) {
            $product->is_social_responsible = 1;
        }
        
        //save product to database
        $product->save();


        // work with variants and options
        foreach ($data['variants'] as  $variant) {
            if (!isset($variant["sku"])) {
                $variantSKU = str_replace(" ", "", $product->title);
                foreach ($variant['options'] as  $variantOption) {
                    $variantSKU .= '-' . $variantOption["title"];
                }
            } else {
                $variantSKU = $variant["sku"];
            }

            $productVariant = Variant::create([
                'sku' => $variantSKU,
                'product_id' => $product->id,
                'options' => [$data['language'] => $variant["options"]],
                'languages' => [$data['language']],
                'price' => $variant["price"],
                'compareAtPrice' => isset($variant["compareAtPrice"]) ? $variant["compareAtPrice"] : null,
                'quantity' => $variant["quantity"],
                'is_default' => $variant["default"],
                'videos' => isset($variant["videos"]) ? $variant["videos"] : [],
            ]);

            if (isset($variant["images"])) {
                foreach ($variant["images"] as  $image) {
                    MediaController::addMediaFromBased64(
                        model: $productVariant,
                        based64String: $image['image'],
                        withResponsiveImages: true,
                        properties: [
                            "default" => in_array("default", $image['options'])
                        ]
                    );
                }
            }
        }

        return response([
            "product" => $product
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $product_id
     * @return \Illuminate\Http\Response
     */
    public function show($product_id)
    {
        $product = $this->product($product_id);
        return response([
            "product" => $product
        ]);
    }


    public function product($product_id)
    {
        $product = Product::findOrFail($product_id);

        $productData = [
            'id' => $product->id,
            'eyebrow_text' => $product->eyebrow_text,
            'title' => $product->title,
            'description' => $product->description,
            'meta_desc' => $product->meta_desc,
            'code' => $product->code,
            'category_id' => $product->category_id,
            'languages' => $product->languages,
            'status' => $product->status,
            'options' => $product->variant_options,
            'variants' => $product->variants,
            'usage' => $product->usage,
        ];

        return $productData;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $product_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, $this->productValidationRules([
            'variants.*.id' => ['nullable', 'exists:variants,id,deleted_at,NULL'],
            'canceled_variants' => ['nullable', 'array'],
            'canceled_variants.*' => ['numeric', 'exists:variants,id,deleted_at,NULL'],
            'id' => ['required', 'exists:products,id,deleted_at,NULL'],
        ]))->validate();

        $product = Product::find($data["id"]);
        $product->setTranslation('eyebrow_text', $data['language'], $data['eyebrow_text']);
        $product->setTranslation('title', $data['language'], $data['title']);
        $product->setTranslation('description', $data['language'], $data['description']);
        $product->setTranslation('meta_desc', $data['language'], $data['meta_desc']);
        $product->code = $data['code'];
        $product->status = $data['status'];
        $product->category_id = $data['category_id'];

        $product->setTranslation('options', $data['language'], $data['options']);

        if (!in_array($data['language'], $product->languages)) {
            $product->languages = array_merge([$data['language']], $product->languages);
        }
        if ($request->social_responsible) {
            $product->is_social_responsible = 1;
        }
        //save product to database
        $product->save();


        //delete canceled variants
        Variant::whereIn('id', $data['canceled_variants'] ?? [])->get()->each->delete();


        // work with variants and options
        foreach ($data['variants'] as  $variant) {
            if (!isset($variant["sku"])) {
                $variantSKU = str_replace(" ", "", $product->title);
                foreach ($variant['options'] as  $variantOption) {
                    $variantSKU .= '-' . $variantOption["title"];
                }
            } else {
                $variantSKU = $variant["sku"];
            }


            if (isset($variant["id"])) {
                $productVariant = Variant::find($variant["id"]);
                $productVariant->sku = $variantSKU;
                $productVariant->setTranslation('options', $data['language'], $variant['options']);
                $productVariant->price = $variant['price'];
                $productVariant->compareAtPrice = $variant['compareAtPrice'] ?? null;
                $productVariant->quantity = $variant['quantity'];
                $productVariant->is_default = $variant['default'];
                $productVariant->videos = isset($variant["videos"]) ? $variant["videos"] : [];

                if (!in_array($data['language'], $product->languages)) {
                    $product->languages = array_merge([$data['language']], $product->languages);
                }

                $productVariant->save();
            } else {
                $productVariant = Variant::create([
                    'sku' => $variantSKU,
                    'product_id' => $product->id,
                    'options' => [$data['language'] => $variant["options"]],
                    'languages' => [$data['language']],
                    'price' => $variant["price"],
                    'compareAtPrice' => isset($variant["compareAtPrice"]) ? $variant["compareAtPrice"] : null,
                    'quantity' => $variant["quantity"],
                    'is_default' => $variant["default"],
                    'videos' => isset($variant["videos"]) ? $variant["videos"] : [],
                ]);
            }


            if (isset($variant["images"])) {
                foreach ($variant["images"] as  $image) {
                    MediaController::addMediaFromBased64(
                        model: $productVariant,
                        based64String: $image['image'],
                        withResponsiveImages: true,
                        properties: [
                            "default" => in_array("default", $image['options'])
                        ]
                    );
                }
            }
        }

        return response([
            "product" => $this->product($product->id)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($product_id)
    {
        $product = Product::findOrFail($product_id);
        $product->delete();

        return response()->json([
            'success' => true
        ]);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addProductUsage(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, [
            'product_id' => ['required', 'exists:products,id'],
            'title' => ['required', 'string', 'max:200'],
            'language' => ['required', 'string', Rule::in(config('app.supported_locales'))],
            'steps' => ['required', 'array'],
            'steps.*' => ['array',],
            'steps.*.description' => ['required', 'string'],
            'steps.*.image' => ['required', 'imageable',],
            'steps.*.video' => ['required', 'string'],
        ])->validate();

        $productUsage = new ProductUsage();
        $productUsage->setTranslation('title', $data['language'], $data['title']);
        $productUsage->languages = [$data['language']];
        $productUsage->product_id = $data['product_id'];
        $productUsage->save();



        foreach ($data["steps"] as  $step) {

            $usageStep = new UsageStep();
            $usageStep->setTranslation('title', $data['language'], $step['title']);
            $usageStep->setTranslation('description', $data['language'], $step['description']);
            $usageStep->languages = [$data['language']];
            $usageStep->video = $step['video'];
            $usageStep->product_usage_id = $productUsage->id;
            $usageStep->save();

            MediaController::addMediaFromBased64(
                model: $usageStep,
                based64String: $step['image'],
                withResponsiveImages: true
            );
        }

        $productUsage->steps;

        return response([
            "product_usage" => $productUsage
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProductUsage(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, [
            'product_usage_id' => ['required', 'exists:product_usages,id'],
            'title' => ['required', 'string', 'max:200'],
            'language' => ['required', 'string', Rule::in(config('app.supported_locales'))],
            'steps' => ['nullable', 'array'],
            'steps.*' => ['array',],
            'steps.*.id' => ['nullable', 'exists:usage_steps,id'],
            'steps.*.description' => ['required', 'string'],
            'steps.*.video' => ['required', 'string'],
            'steps.*.image' => ['nullable', 'imageable',],
        ])->validate();

        $productUsage = ProductUsage::find($data['product_usage_id']);
        $productUsage->setTranslation('title', $data['language'], $data['title']);
        if (!in_array($data['language'], $productUsage->languages)) {
            $productUsage->languages = array_merge([$data['language']], $productUsage->languages);
        }
        $productUsage->save();


        if (isset($data['steps'])) {

            foreach ($data["steps"] as  $step) {

                if (isset($step['id'])) {
                    $usageStep = UsageStep::find($step['id']);
                } else {
                    $usageStep = new UsageStep();
                }

                $usageStep->setTranslation('title', $data['language'], $step['title']);
                $usageStep->setTranslation('description', $data['language'], $step['description']);

                if (is_array($usageStep->languages)) {
                    if (!in_array($data['language'], $usageStep->languages)) {
                        $usageStep->languages = array_merge([$data['language']], $usageStep->languages);
                    }
                } else {
                    $usageStep->languages = [$data['language']];
                }

                $usageStep->video = $step['video'];
                $usageStep->product_usage_id = $productUsage->id;

                $usageStep->save();

                if (isset($step['image'])) {
                    MediaController::addMediaFromBased64(
                        model: $usageStep,
                        based64String: $step['image'],
                        withResponsiveImages: true
                    );
                }
            }
        }


        $productUsage = ProductUsage::find($data['product_usage_id']);

        return response([
            "product_usage" => $productUsage
        ]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductUsage  $product_usage_id
     * @return \Illuminate\Http\Response
     */
    public function destroyProductUsage($product_usage_id)
    {
        $product_usage = ProductUsage::findOrFail($product_usage_id);
        $product_usage->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
