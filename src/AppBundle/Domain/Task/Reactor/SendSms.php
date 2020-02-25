<?php

namespace AppBundle\Domain\Task\Reactor;

use AppBundle\Domain\Task\Event\TaskDone;
use AppBundle\Entity\Task;
use AppBundle\Message\Sms;
use AppBundle\Service\SettingsManager;
use Hashids\Hashids;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendSms
{
    private $settingsManager;
    private $messageBus;

    public function __construct(
        SettingsManager $settingsManager,
        MessageBusInterface $messageBus,
        PhoneNumberUtil $phoneNumberUtil,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        string $secret)
    {
        $this->settingsManager = $settingsManager;
        $this->messageBus = $messageBus;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->secret = $secret;
    }

    public function __invoke(TaskDone $event)
    {
        $smsEnabled = $this->settingsManager->get('sms_enabled');

        if (!$smsEnabled) {
            return;
        }

        $task = $event->getTask();

        if (!$task->isPickup()) {
            return;
        }

        $delivery = $task->getDelivery();

        if (null === $delivery) {
            return;
        }

        $order = $delivery->getOrder();

        // Skip if this is related to foodtech
        if (null !== $order && $order->isFoodtech()) {
            return;
        }

        $store = $delivery->getStore();

        if (null === $store || !$store->isSmsEnabled()) {
            return;
        }

        $dropoff = $delivery->getDropoff();

        $telephone = $dropoff->getAddress()->getTelephone();

        if (!$telephone) {
            return;
        }

        $telephone = $this->phoneNumberUtil->format($telephone, PhoneNumberFormat::E164);

        $hashids = new Hashids($this->secret, 8);

        $trackingUrl = $this->urlGenerator->generate('public_delivery', [
            'hashid' => $hashids->encode($delivery->getId())
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $text = $this->translator->trans('sms.tracking', ['%link%' => $trackingUrl]);

        $this->messageBus->dispatch(
            new Sms($text, $telephone)
        );
    }
}
