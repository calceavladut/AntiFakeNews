<?php

namespace App\Form;

use App\Entity\ExtractedArticle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('url', TextType::class, array(
        'label' => false,
        'attr' => array(
            'placeholder' => 'It is FAKE?'
        )
    ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExtractedArticle::class,
        ]);
    }

}
