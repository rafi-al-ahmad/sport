<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasTranslations, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';


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
        'eyebrow_text',
        'title',
        'description',
        'meta_desc',
        'code',
        'category_id',
        'options',
        'languages',
        'status',
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
        'options' => 'array',
    ];

    /**
     * The attributes that are have many translations.
     *
     * @var array
     */
    public $translatable = [
        'eyebrow_text',
        'title',
        'description',
        'meta_desc',
        'options',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'variants',
    ];

    
    // declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($model) {
             $model->variants()->delete();
             $model->kits()->detach();
        });
    }

    /**
     * Get the model's title by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function eyebrowText(): Attribute
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
     * Get the product options by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function options(): Attribute
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
     * Get the model's meta description by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function metaDesc(): Attribute
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
     * set the language should this model translated to
     */
    public function setDisplyLanguage($local)
    {
        $this->displayLanguage = $local;
    }

    public function variants()
    {
        return $this->hasMany(Variant::class, 'product_id', 'id')->withOut('product');
    }

    public function usage()
    {
        return $this->hasOne(ProductUsage::class, 'product_id', 'id');
    }

    public function kits()
    {
        return $this->belongsToMany(Kit::class, 'kit_products', 'product_id', 'kit_id');
    }
}
