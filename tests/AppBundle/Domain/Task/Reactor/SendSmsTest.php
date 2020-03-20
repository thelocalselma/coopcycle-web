<?php

namespace Tests\AppBundle\Domain\Task\Reactor;

use AppBundle\Domain\Task\Event\TaskDone;
use AppBundle\Domain\Task\Reactor\SendSms;
use AppBundle\Entity\Address;
use AppBundle\Entity\ApiUser;
use AppBundle\Entity\Delivery;
use AppBundle\Entity\Store;
use AppBundle\Entity\Task;
use AppBundle\Service\EmailManager;
use AppBundle\Service\SettingsManager;
use AppBundle\Sylius\Order\OrderInterface;
use AppBundle\Message\Sms;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendSmsTest extends TestCase
{
    private $sendEmail;

    public function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->settingsManager = $this->prophesize(SettingsManager::class);
        $this->phoneNumberUtil = $this->prophesize(PhoneNumberUtil::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->sendSms = new SendSms(
            $this->settingsManager->reveal(),
            $this->messageBus->reveal(),
            $this->phoneNumberUtil->reveal(),
            $this->urlGenerator->reveal(),
            $this->translator->reveal(),
            'foobar'
        );
    }

    public function testSendsSmsWithSmsDisabled()
    {
        $this->settingsManager->get('sms_enabled')->willReturn(false);

        $pickup = new Task();
        $pickup->setType(Task::TYPE_PICKUP);

        $msg = new Sms('Hello', '+33612345678');

        $this->messageBus
            ->dispatch($msg)
            ->shouldNotBeCalled();

        call_user_func_array($this->sendSms, [ new TaskDone($pickup, 'Lorem ipsum') ]);
    }

    public function testSendsSmsWithDropoff()
    {
        $this->settingsManager->get('sms_enabled')->willReturn(false);

        $dropoff = new Task();
        $dropoff->setType(Task::TYPE_DROPOFF);

        $msg = new Sms('Hello', '+33612345678');

        $this->messageBus
            ->dispatch($msg)
            ->shouldNotBeCalled();

        call_user_func_array($this->sendSms, [ new TaskDone($dropoff, 'Lorem ipsum') ]);
    }

    public function testSendsSmsWithoutTelephone()
    {
        $this->settingsManager->get('sms_enabled')->willReturn(true);

        $phoneNumber = new PhoneNumber();

        $this->phoneNumberUtil->format($phoneNumber, Argument::any())
            ->willReturn('+33612345678');

        $delivery = new Delivery();

        $dropoffAddress = new Address();
        $delivery->getDropoff()->setAddress($dropoffAddress);

        $msg = new Sms('Hello', '+33612345678');

        $this->messageBus
            ->dispatch($msg)
            ->shouldNotBeCalled();

        call_user_func_array($this->sendSms, [ new TaskDone($delivery->getPickup(), 'Lorem ipsum') ]);
    }

    public function testSendsSmsToRecipient()
    {
        $this->settingsManager->get('sms_enabled')->willReturn(true);

        $phoneNumber = new PhoneNumber();

        $this->phoneNumberUtil->format($phoneNumber, Argument::any())
            ->willReturn('+33612345678');

        $dropoffAddress = new Address();
        $dropoffAddress->setTelephone($phoneNumber);

        $delivery = $this->prophesize(Delivery::class);

        $delivery->getOrder()->willReturn(null);
        $delivery->getId()->willReturn(1);

        $pickup = new Task();
        $pickup->setType(Task::TYPE_PICKUP);

        $dropoff = new Task();
        $dropoff->setType(Task::TYPE_DROPOFF);
        $dropoff->setAddress($dropoffAddress);

        $delivery->getPickup()->willReturn($pickup);
        $delivery->getDropoff()->willReturn($dropoff);

        $pickup->setDelivery($delivery->reveal());

        $this->urlGenerator
            ->generate('public_delivery', ['hashid' => 'p5oXEQvJ'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://bit.ly/abcdef')
            ;

        $text = 'Track delivery at http://bit.ly/abcdef';

        $this->translator
            ->trans('sms.tracking', ['%link%' => 'http://bit.ly/abcdef'])
            ->willReturn($text)
            ;

        $msg = new Sms($text, '+33612345678');

        // @see https://github.com/symfony/symfony/issues/33740
        $this->messageBus
            ->dispatch($msg)
            ->shouldBeCalled()
            ->willReturn(new Envelope($msg));

        call_user_func_array($this->sendSms, [ new TaskDone($delivery->reveal()->getPickup(), 'Lorem ipsum') ]);
    }
}
