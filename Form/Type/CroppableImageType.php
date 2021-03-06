<?php

namespace Comur\ImageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\FormBuilder;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CroppableImageType extends AbstractType
{

    protected $isGallery = false;
    protected $galleryDir = null;
    protected $thumbsDir = null;

    // public function getParent()
    // {
    //     return 'text';
    // }

    public function getName()
    {
        return 'comur_image';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // if($options['uploadConfig']['saveOriginal']){
        //     $form->getParent()->add($options['uploadConfig']['saveOriginal'], 'hidden');
        // }
        // var_dump($builder->getDataMapper());exit;
        if($options['uploadConfig']['saveOriginal']){
            $builder->add($options['uploadConfig']['saveOriginal'], 'text', array(
                // 'inherit_data' => true,
                // 'property_path' => $options['uploadConfig']['saveOriginal'],
                'attr' => array('style' => 'opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0; position: absolute;')));
        }
        $builder->add($builder->getName(), 'text', array(
            // 'property_path' => $builder->getName(),
            // 'inherit_data' => true,
            'attr' => array('style' => 'opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0; position: absolute;')));
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $uploadConfig = array(
            'uploadRoute' => 'comur_api_upload',
            'uploadUrl' => null,
            'webDir' => null,
            'fileExt' => '*.jpg;*.gif;*.png;*.jpeg',
            'libraryDir' => null,
            'libraryRoute' => 'comur_api_image_library',
            'showLibrary' => true,
            'saveOriginal' => false, //save original file name
        );

        $cropConfig = array(
            // 'disableCrop' => false,
            'minWidth' => 1,
            'minHeight' => 1,
            'aspectRatio' => true,
            'cropRoute' => 'comur_api_crop',
            'forceResize' => true,
            'thumbs' => null
        );

        $resolver->setDefaults(array(
            'uploadConfig' => $uploadConfig,
            'cropConfig' => $cropConfig,
            // 'compound' => function(Options $options, $value) use($cropConfig){
            //     return $options['uploadConfig']['saveOriginal'] ? true : false;
            // },
            'inherit_data' => true,
            'help' => null,
            'show_fade' => false
            // 'property_path' => null,
            // 'data_class' => 'MVB\Bundle\MemberBundle\Entity\Member'
        ));

        $resolver->setOptional(array('help', 'show_fade', 'image_checker', 'image_path'));

        $isGallery = $this->isGallery;
        $galleryDir = $this->galleryDir;

        $resolver->setNormalizers(array(
            'uploadConfig' => function(Options $options, $value) use ($uploadConfig, $isGallery, $galleryDir){
                $config = array_merge($uploadConfig, $value);

                if($isGallery){
                    $config['uploadUrl'] = $config['uploadUrl'].'/'.$galleryDir;
                    $config['webDir'] = $config['webDir'].'/'.$galleryDir;
                    $config['saveOriginal'] = false;
                }

                if(!isset($config['libraryDir'])){
                    $config['libraryDir'] = $config['uploadUrl'];
                }
                // if($config['saveOriginal']){
                //     $options['compound']=true;
                // }
                return $config;
            },
            'cropConfig' => function(Options $options, $value) use($cropConfig){
                return array_merge($cropConfig, $value);
            }
            // 'compound' => function(Options $options, $value) use($cropConfig){
            //     return $options['uploadConfig']['saveOriginal'] ? true : false;
            // }
        ));
        
    }

    /**
     * {@inheritdoc}
     */
    // public function finishView(FormView $view, FormInterface $form, array $options)
    // {
    //     var_dump($form->getParent()->get($options['uploadConfig']['saveOriginal']));exit;
    // }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $originalPhotoFieldId = null;

        $uploadConfig = $options['uploadConfig'];
        $cropConfig = $options['cropConfig'];

        $fieldImage = null;
        if(isset($cropConfig['thumbs']) && count($thumbs = $cropConfig['thumbs']) > 0)
        {
            foreach ($thumbs as $thumb) {
                if(isset($thumb['useAsFieldImage']) && $thumb['useAsFieldImage'])
                {
                    $fieldImage = $thumb;
                }
            }
        }

        $view->vars['options'] = array('uploadConfig' => $uploadConfig, 'cropConfig' => $cropConfig, 'fieldImage' => $fieldImage);
        $view->vars['attr'] = array('style' => 'opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0; position: absolute;');

        if (array_key_exists('help', $options)) {
            $view->vars['help'] = $options['help'];
        }

        if (array_key_exists('show_fade', $options)) {
            $view->vars['show_fade'] = $options['show_fade'];
        }


        if ((array_key_exists('image_path', $options)) && (array_key_exists('image_checker', $options))) {
            $parentData = $form->getParent()->getData();

            $imageUrl = null;
            if (null !== $parentData) {
                $accessor = PropertyAccess::createPropertyAccessor();
                if (true === $accessor->getValue($parentData, $options['image_checker'])) {
                    $imageUrl = $accessor->getValue($parentData, $options['image_path']);
                }
            }

            // set an "image_url" variable that will be available when rendering this field
            $view->vars['image_url'] = $imageUrl;

            $view->vars['image_url_faded'] = $imageUrl;
        }
    }
}
