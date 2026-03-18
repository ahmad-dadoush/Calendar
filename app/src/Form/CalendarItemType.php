<?php

namespace App\Form;

use App\Entity\CalendarItem;
use DateTimeImmutable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class CalendarItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('itemId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-calendar-target' => 'itemId',
                ],
            ])
            ->add('day', IntegerType::class, [
                'label' => 'Tag',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'data-calendar-target' => 'day',
                    'min' => 1,
                    'max' => 31,
                    'placeholder' => 'TT',
                    'inputmode' => 'numeric',
                ],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib einen Tag ein.'),
                    new Range(
                        min: 1,
                        max: 31,
                        notInRangeMessage: 'Der Tag muss zwischen {{ min }} und {{ max }} liegen.'
                    ),
                ],
            ])
            ->add('month', IntegerType::class, [
                'label' => 'Monat',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'data-calendar-target' => 'month',
                    'min' => 1,
                    'max' => 12,
                    'placeholder' => 'MM',
                    'inputmode' => 'numeric',
                ],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib einen Monat ein.'),
                    new Range(
                        min: 1,
                        max: 12,
                        notInRangeMessage: 'Der Monat muss zwischen {{ min }} und {{ max }} liegen.'
                    ),
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Bezeichnung',
                'attr' => [
                    'data-calendar-target' => 'description',
                    'maxlength' => 255,
                ],
            ])
            ->add('reminderDays', ChoiceType::class, [
                'label' => 'Erinnerung',
                'placeholder' => '--bitte auswählen--',
                'attr' => [
                    'data-calendar-target' => 'reminderDays',
                ],
                'choices' => CalendarItem::getReminderChoices(),
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data instanceof CalendarItem) {
                return;
            }

            $date = $data->getDate();

            if (null === $date) {
                return;
            }

            $form->get('day')->setData((int) $date->format('d'));
            $form->get('month')->setData((int) $date->format('m'));
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!$data instanceof CalendarItem) {
                return;
            }

            $day = $form->get('day')->getData();
            $month = $form->get('month')->getData();

            if (null === $day || null === $month || !is_numeric($day) || !is_numeric($month)) {
                $data->setDate(null);

                return;
            }

            $day = (int) $day;
            $month = (int) $month;
            $year = (int) (new DateTimeImmutable())->format('Y');

            if (!checkdate($month, $day, $year)) {
                $form->get('day')->addError(new FormError('Tag und Monat ergeben kein gueltiges Datum.'));
                $data->setDate(null);

                return;
            }

            $data->setDate(new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day)));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarItem::class,
            'attr' => [
                'data-calendar-target' => 'form',
                'data-action' => 'submit->calendar#submit',
            ],
        ]);
    }
}