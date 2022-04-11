<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class UsageStep extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasTranslations;

     /**
     * The language that should translated to
     */
    public $displayLanguage;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'video',
        'product_usage_id',
        'description',
        'languages',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'media',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'languages' => 'array',
    ];

    /**
     * The attributes that are have many translations.
     *
     * @var array
     */
    public $translatable = [
        'title',
        'description',
    ];

    
    
    protected $appends = [
        'image',
    ];


    /**
     * Get the model's title by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $decodedValue = json_decode($value, true);
                if ($this->displayLanguage) {
                    if (isset($decodedValue[$this->displayLanguage])) {
                        return $decodedValue[$this->displayLanguage];
                    }
                }
                if (isset($decodedValue[App::currentLocale()])) {
                    return $decodedValue[App::currentLocale()];
                }
                return '';
            }
        );
    }

    /**
     * Get the model's description by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $decodedValue = json_decode($value, true);
                if ($this->displayLanguage) {
                    if (isset($decodedValue[$this->displayLanguage])) {
                        return $decodedValue[$this->displayLanguage];
                    }
                }
                if (isset($decodedValue[App::currentLocale()])) {
                    return $decodedValue[App::currentLocale()];
                }
                return '';
            }
        );
    }


    /**
     * prepare the media strcture for this model
     * 
     * @return array
     */
    public function getImageAttribute()
    {
        $mediaElement = $this->getFirstMedia();
        $image = [];

        if (isset($mediaElement)) {
            $image['id'] = $mediaElement->id;
            $image['file_name'] = $mediaElement->file_name;
            $image['mime_type'] = $mediaElement->mime_type;
            $image['size'] = $mediaElement->size;
            $image['url'] = $mediaElement->getUrl();
            $image['srcset'] = $mediaElement->getSrcset();
        }

        return $image;
    }
}
