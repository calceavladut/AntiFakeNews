<?php

namespace App\Form;

use App\Entity\ExtractedArticle;
use AtLeastOneField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'url',
                TextType::class,
                [
                    'required' => false
                ]
            )
            ->add(
                'text',
                TextareaType::class,
                [
                    'required' => false
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'Get enlightened',
                    'attr'  => ['class' => 'btn submit'],
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'  => ExtractedArticle::class,
            'constraints' => new AtLeastOneField(['path' => 'url'])
       ]);
    }
}
