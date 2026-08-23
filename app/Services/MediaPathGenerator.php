<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Lays the media out under the model it belongs to — `exercises/42/7/…` —
 * instead of the package default, which drops every file in a directory named
 * after the media id alone.
 *
 * The point is a browsable bucket: everything an exercise owns sits under one
 * prefix, so an illustration can be traced back to its exercise, and dropping
 * an exercise's files by hand is a single prefix delete.
 *
 * The media id stays as the last segment: two files attached to the same
 * exercise may carry the same name, and the id is what keeps them apart.
 */
class MediaPathGenerator implements PathGenerator
{
    /**
     * Where the original file lives.
     */
    public function getPath(Media $media): string
    {
        return $this->basePath($media);
    }

    /**
     * Where the conversions of that file live.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media).'conversions/';
    }

    /**
     * Where the responsive variants of that file live.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media).'responsive-images/';
    }

    /**
     * The prefix every file of one media shares, trailing slash included.
     */
    protected function basePath(Media $media): string
    {
        return $this->directoryFor($media).'/'.$media->model_id.'/'.$media->id.'/';
    }

    /**
     * The directory standing for the owning model: `Exercise` gives
     * `exercises`. Morph aliases are resolved first so a map added later does
     * not rename the directories underneath.
     */
    protected function directoryFor(Media $media): string
    {
        $model = Relation::getMorphedModel($media->model_type) ?? $media->model_type;

        return Str::of(class_basename($model))->kebab()->plural()->toString();
    }
}
