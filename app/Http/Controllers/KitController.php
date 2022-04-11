<?php

namespace App\Http\Controllers;

use App\Http\Resources\KitResource;
use App\Models\Kit;
use App\Models\KitProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class KitController extends Controller
{
    protected function validationRules($rules = [])
    {
        return array_merge([
            'eyebrow_text' => ['required', 'max:190'],
            'title' => ['required', 'max:190'],
            'description' => ['required', 'string'],
            'meta_desc' => ['required', 'string'],
            'status' => ['required', 'in:0,1'],
            'language' => ['required', 'string', Rule::in(config('app.supported_locales'))],
            'discount' => ['required', 'numeric', "max:1", 'min:0'],
            'products' => ['nullable', 'array'],
            'products.*' => ['numeric', 'exists:products,id,deleted_at,NULL'],
            'image' => ['nullable', 'imageable'],
        ], $rules);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return KitResource::collection(Kit::paginate($request->limit));
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function activeWithFilters(Request $request)
    {
        $query = Kit::where('status', 1);

        if (isset($request->key)) {
            $query->where(function($query) use ($request) {
                $query->orWhere('title', 'like', '%'.$request->key.'%')
                ->orWhere('description', 'like', '%'.$request->key.'%')
                ->orWhere('meta_desc', 'like', '%'.$request->key.'%');
            });
        }
        return KitResource::collection($query->paginate($request->limit));
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
        Validator::make($data, $this->validationRules())->validate();

        $kit = new Kit();
        $kit->setTranslation('eyebrow_text', $data['language'], $data['eyebrow_text']);
        $kit->setTranslation('title', $data['language'], $data['title']);
        $kit->setTranslation('description', $data['language'], $data['description']);
        $kit->setTranslation('meta_desc', $data['language'], $data['meta_desc']);
        $kit->discount = $data['discount'];
        $kit->status = $data['status'];
        $kit->languages = [$data['language']];
        //save kit to database
        $kit->save();

        if (isset($data["image"])) {
            MediaController::addMediaFromBased64(
                model: $kit,
                based64String: $data['image'],
                withResponsiveImages: true
            );
        }

        if (isset($data['products'])) {
            foreach ($data["products"] as $product_id) {
                KitProduct::create([
                    "product_id" => $product_id,
                    "kit_id" => $kit->id,
                ]);
            }
        }

        return response([
            "kit" => $kit
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $kit = Kit::findOrFail($id);

        return response([
            "kit" => $kit
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, $this->validationRules([
            'id' => ['required', 'exists:kits,id,deleted_at,NULL'],
            'canceled_products' => ['nullable', 'array'],
            'canceled_products.*' => ['numeric'],
        ]))->validate();

        $kit = Kit::find($data["id"]);
        $kit->setTranslation('eyebrow_text', $data['language'], $data['eyebrow_text']);
        $kit->setTranslation('title', $data['language'], $data['title']);
        $kit->setTranslation('description', $data['language'], $data['description']);
        $kit->setTranslation('meta_desc', $data['language'], $data['meta_desc']);
        $kit->discount = $data['discount'];
        $kit->status = $data['status'];
        $kit->languages = [$data['language']];
        //save kit to database
        $kit->save();

        
        if (isset($data["image"])) {
            MediaController::addMediaFromBased64(
                model: $kit,
                based64String: $data['image'],
                withResponsiveImages: true
            );
        }

        if (isset($data['products'])) {
            foreach ($data["products"] as $product_id) {
                KitProduct::firstOrCreate([
                    "product_id" => $product_id,
                    "kit_id" => $kit->id,
                ]);
            }
        }


        if (isset($data['canceled_products'])) {
            KitProduct::where("kit_id", $kit->id)->whereIn('product_id', $data["canceled_products"])->delete();
        }

        return response([
            "kit" => $kit
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $kit = Kit::findOrFail($id);
        $kit->delete();

        return response([
            "success" => true
        ]);
    }
}
