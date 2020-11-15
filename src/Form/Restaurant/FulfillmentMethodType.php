<?php

namespace AppBundle\Form\Restaurant;

use AppBundle\Entity\LocalBusiness\FulfillmentMethod;
use AppBundle\Form\Type\MoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FulfillmentMethodType extends AbstractType
{
    private $authorizationChecker;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('openingHours', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'entry_options' => [
                    'error_bubbling' => false
                ],
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'label' => 'localBusiness.form.openingHours',
                'error_bubbling' => false
            ])
            ->add('openingHoursBehavior', ChoiceType::class, [
                'label' => 'localBusiness.form.openingHoursBehavior',
                'choices'  => [
                    'localBusiness.form.openingHoursBehavior.asap' => 'asap',
                    'localBusiness.form.openingHoursBehavior.time_slot' => 'time_slot',
                ],
                'expanded' => true,
                'multiple' => false,
            ]);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $builder->add('allowEdit', CheckboxType::class, [
                'label' => 'basics.allow_edit',
                'required' => false,
                'mapped' => false,
            ]);
        }

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $fulfillmentMethod = $event->getData();

            $allowEdit =
                ($fulfillmentMethod->hasOption('allow_edit') && true === $fulfillmentMethod->getOption('allow_edit'));

            if ($form->has('allowEdit')) {
                $form->get('allowEdit')->setData($allowEdit);
            }

            $form
                ->add('minimumAmount', MoneyType::class, [
                    'label' => 'restaurant.contract.minimumCartAmount.label',
                    'disabled' => !$allowEdit,
                ])
                ->add('orderingDelayDays', IntegerType::class, [
                    'label' => 'localBusiness.form.orderingDelayDays',
                    'mapped' => false,
                    'disabled' => !$allowEdit,
                ])
                ->add('orderingDelayHours', IntegerType::class, [
                    'label' => 'localBusiness.form.orderingDelayHours',
                    'mapped' => false,
                    'disabled' => !$allowEdit,
                ]);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {

            $form = $event->getForm();
            $fulfillmentMethod = $form->getData();

            // Make sure there is no NULL value in the openingHours array
            $fulfillmentMethod->setOpeningHours(
                array_filter($fulfillmentMethod->getOpeningHours())
            );

            if ($form->has('allowEdit')) {
                $fulfillmentMethod->setOption(
                    'allow_edit',
                    $form->get('allowEdit')->getData()
                );
            }
        });

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

            $fulfillmentMethod = $event->getData();
            $form = $event->getForm();

            $orderingDelayMinutes = $fulfillmentMethod->getOrderingDelayMinutes();
            $orderingDelayDays = $orderingDelayMinutes / (60 * 24);
            $remainder = $orderingDelayMinutes % (60 * 24);
            $orderingDelayHours = $remainder / 60;

            $form->get('orderingDelayHours')->setData($orderingDelayHours);
            $form->get('orderingDelayDays')->setData($orderingDelayDays);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {

            $form = $event->getForm();
            $fulfillmentMethod = $form->getData();

            $orderingDelayDays = $form->get('orderingDelayDays')->getData();
            $orderingDelayHours = $form->get('orderingDelayHours')->getData();

            $fulfillmentMethod->setOrderingDelayMinutes(
                ($orderingDelayDays * 60 * 24) + ($orderingDelayHours * 60)
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => FulfillmentMethod::class,
        ));
    }
}
