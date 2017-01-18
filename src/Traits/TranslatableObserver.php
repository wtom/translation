<?php namespace Waavi\Translation\Traits;

use Waavi\Translation\Models\Translation;
use Waavi\Translation\Repositories\LanguageRepository;
use Waavi\Translation\Repositories\TranslationRepository;
use Illuminate\Database\Eloquent\Model;
class TranslatableObserver
{
    /**
     *  Look at all avaible translation before saving
     * @param Model $model
     * @return void
     */
    public function creating(Model $model){

        $translationRepository = \App::make(TranslationRepository::class);
        $locales    = \App::make(LanguageRepository::class)->all();


        foreach ($model->translatableAttributes() as $attribute) {

            //check for multiple translations being saved
            if(is_array($model->getRawAttribute($attribute))){

                foreach ($model->getRawAttribute($attribute) as $locale => $translation){
                    if($translation != ''){
                        $translationRepository->updateByCode($locale, $model->translationCodeFor($attribute), $translation);
                    }
                }

                //set attribute to default localized string
                $model->setAttribute($attribute, $model->getRawAttribute($attribute)[$translationRepository->defaultLocale]);
            }

        }
    }

    /**
     *  Look at all avaible translation before saving
     * @param Model $model
     * @return void
     */
    public function updating(Model $model){

        $translationRepository = \App::make(TranslationRepository::class);
        $locales    = \App::make(LanguageRepository::class)->all();


        foreach ($model->translatableAttributes() as $attribute) {

            //check for multiple translations being saved
            if(is_array($model->getRawAttribute($attribute))){

                foreach ($model->getRawAttribute($attribute) as $locale => $translation){
                    if($translation != ''){
                        if($model->translate($attribute, $locale) != $translation){
                            $translationRepository->updateByCode($locale, $model->translationCodeFor($attribute), $translation);
                        }
                    }
                    else{
                        $translationRepository->deleteByCodeAndLocale($model->translationCodeFor($attribute), $locale);
                    }
                }

                //set attribute to default localized string
                $model->setAttribute($attribute, $model->getRawAttribute($attribute)[$translationRepository->defaultLocale]);
            }

        }
    }


    /**
     *  Save translations when model is saved.
     *
     *  @param  Model $model
     *  @return void
     */
    public function saved($model)
    {
        $translationRepository = \App::make(TranslationRepository::class);
        $languageRepository    = \App::make(LanguageRepository::class);
        $cacheRepository       = \App::make('translation.cache.repository');


        foreach ($model->translatableAttributes() as $attribute) {
            // If the value of the translatable attribute has changed:
            if ($model->isDirty($attribute)) {
                $translationRepository->updateDefaultByCode($model->translationCodeFor($attribute), $model->getRawAttribute($attribute));
            }
        }

        foreach ($model->translatableAttributesForLocales($languageRepository->all()) as $attribute) {

            if($model->getRawAttribute($attribute)){
                $translationRepository->updateByCode($model->translationCodeFor($attribute), $model->getRawAttribute($attribute));
            }

        }


        $cacheRepository->flush(config('app.locale'), 'translatable', '*');
    }

    /**
     *  Delete translations when model is deleted.
     *
     *  @param  Model $model
     *  @return void
     */
    public function deleted($model)
    {
        $translationRepository = \App::make(TranslationRepository::class);
        foreach ($model->translatableAttributes() as $attribute) {
            $translationRepository->deleteByCode($model->translationCodeFor($attribute));
        }
    }
}
