<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{

    protected function reviewValidationRules($rules = [])
    {
        return array_merge([
            'stars' => ['required', 'numeric', "max:5", 'min:0'],
            'content' => ['nullable', 'string'],
            'product_id' => ['numeric', 'exists:products,id,deleted_at,NULL'],
        ], $rules);
    }

    protected function replyValidationRules($rules = [])
    {
        return array_merge([
            'content' => ['required', 'string'],
            'review_id' => ['numeric', 'exists:reviews,id,deleted_at,NULL'],
        ], $rules);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Review::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('order')) {
            $query->orderBy('id', $request->order ? 'DESC' : 'ASC');
        }

        return ReviewResource::collection($query->paginate($request->limit));
    }




    /**
     * Display a listing of the activated reviews.
     *
     * @return \Illuminate\Http\Response
     */
    public function activated(Request $request, $product_id)
    {
        $query = Review::where('product_id', $product_id);

        if ($request->has('status')) {
            $query->where('status', 1);
        }

        if ($request->has('order')) {
            $query->orderBy('id', $request->order ? 'DESC' : 'ASC');
        }

        $avg_stars = $query->clone()->avg('stars_value');
        return ReviewResource::collection($query->paginate($request->limit))->additional(["stars_avarge" => $avg_stars]);
    }




    /**
     * Store a newly created resource or in storage or update existes one.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeOrUpdate(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, $this->reviewValidationRules())->validate();

        $review = Review::updateOrCreate(
            [
                'product_id' => $data['product_id'],
                'user_id' => $this->currentUserId(),
            ],
            [
                'content' => $data['content'],
                'stars_value' => $data['stars'],
                'status' => 0,
            ]
        );

        return response([
            'review' => $review
        ]);
    }


    /**
     * approve review to publish.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approve($id)
    {
        $review  = Review::findOrFail($id);

        $review->update(['status' => 1]);

        return response([
            "review" => $review
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
        $review  = Review::findOrFail($id);

        $review->delete();

        return response([
            "status" => true
        ]);
    }



    /**
     * Store a newly created reply or in storage or update existes one.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addReply(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, $this->replyValidationRules())->validate();

        $reply = ReviewReply::create(
            [
                'review_id' => $data['review_id'],
                'user_id' => $this->currentUserId(),
                'content' => $data['content'],
            ]
        );

        return response([
            'reply' => $reply
        ]);
    }



    /**
     * Store a newly created reply or in storage or update existes one.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateReply(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, $this->replyValidationRules([
            'reply_id' => ['numeric', 'exists:review_replies,id,deleted_at,NULL']
        ]))->validate();

        $reply = ReviewReply::find($data['reply_id']);

        if ($reply->user_id != $this->currentUserId()) {
            abort(403);
        }

        $reply->update(
            [
                'content' => $data['content'],
            ]
        );

        return response([
            'reply' => $reply
        ]);
    }

    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyReply($id)
    {
        $reply  = ReviewReply::findOrFail($id);

        $reply->delete();

        return response([
            "status" => true
        ]);
    }

}
