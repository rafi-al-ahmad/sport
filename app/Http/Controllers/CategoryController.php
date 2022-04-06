<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    protected function validationRules(array $rules = [])
    {
        return array_merge([
            'title' => ['required', 'max:60'],
            'language' => ['required', 'max:30'],
            'status' => ['required', 'in:1,2'],
        ], $rules);
    }



    /**
     * Display a listing of all categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\CategoryResource
     */
    public function index(Request $request)
    {
        return CategoryResource::collection(Category::paginate($request->limit));
    }

    /**
     * Get active categories
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\CategoryResource
     */
    public function active(Request $request)
    {
        $categories = Category::where('status', 1)->paginate($request->limit);
        return CategoryResource::collection($categories);
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
        Validator::make($data, $this->validationRules([]))->validate();

        $categorie = new Category();
        $categorie->setTranslation('title', $data['language'], $data['title']);
        $categorie->status = $data['status'];
        $categorie->languages = [$data['language']];
        $categorie->save();

        return  response([
            'categorie' => $categorie
        ]);
    }


    /**
     * get the specified resource.
     *
     * @param  int $category_id
     * @return \Illuminate\Http\Response
     */
    public function show($category_id)
    {
        $category = Category::findOrFail($category_id);

        return  response([
            'category' => $category
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $category_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $category_id)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, $this->validationRules([]))->validate();

        $category = Category::findOrFail($category_id);

        $category->setTranslation('title', $data['language'], $data['title']);
        $category->status = $data['status'];

        if (!in_array($data['language'], $category->languages)) {
            $category->languages = array_merge([$data['language']], $category->languages);
        }

        $category->save();

        $category->setDisplyLanguage($data['language']);

        return  response([
            'category' => $category
        ]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $category_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($category_id)
    {
        $category = Category::findOrFail($category_id);
        $category->delete();

        return response([
            'success' => true
        ]);
    }

    
    /**
     * Remove the specified translation from the resource.
     *
     * @param  int $category_id
     * @return \Illuminate\Http\Response
     */
    public function deleteTranslation($category_id, $language)
    {
        $category = Category::findOrFail($category_id);
        if (!in_array($language, $category->languages)) {
            return response([
                'category' => $category
            ]);
        }

        $category->forgetTranslation('title', $language);
        $languages = $category->languages;
        unset($languages[array_search($language, $languages)]);
        $category->languages = $languages;
        $category->save();

        return  response([
            'category' => $category
        ]);
    }
}
