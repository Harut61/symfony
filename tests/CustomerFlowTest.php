<?php


namespace App\Tests;


use App\Entity\ChannelPartner;
use App\Entity\Room;
use App\Service\ChannelPartnerService;
use App\Test\ContainerDependableTestCase;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class CustomerFlowTest extends ContainerDependableTestCase
{
    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCustomerFlow()
    {
        $customer = $this->em
            ->getRepository(ChannelPartner::class)
            ->findOneBy(['email' => 'evis.bregu@gmail.com']);

        $registrationRoom = $this->em
            ->getRepository(Room::class)
            ->findOneBy(['code' => '001']);

        $waitingRoom = $this->em
            ->getRepository(Room::class)
            ->findOneBy(['code' => '101']);

        $loungeRoom = $this->em
            ->getRepository(Room::class)
            ->findOneBy(['code' => '201']);

        $modelAvHallRoom = $this->em
            ->getRepository(Room::class)
            ->findOneBy(['code' => '401']);

        $modelRoom = $this->em
            ->getRepository(Room::class)
            ->findOneBy(['code' => '501']);

        $avRoom = $this->em
            ->getRepository(Room::class)
            ->findOneBy(['code' => '502']);

        $flatArea = $this->em
            ->getRepository(Room::class)
            ->findOneBy(['code' => '601']);

        $salesArea = $this->em
            ->getRepository(Room::class)
            ->findOneBy(['code' => '701']);

        $this->assertInstanceOf(Room::class, $registrationRoom);
        $this->assertInstanceOf(Room::class, $waitingRoom);
        $this->assertInstanceOf(Room::class, $loungeRoom);
        $this->assertInstanceOf(Room::class, $modelAvHallRoom);
        $this->assertInstanceOf(Room::class, $modelRoom);
        $this->assertInstanceOf(Room::class, $avRoom);
        $this->assertInstanceOf(Room::class, $flatArea);
        $this->assertInstanceOf(Room::class, $salesArea);

        /** @var ChannelPartnerService $customerService */
        $customerService = $this->get(ChannelPartnerService::class);
        /** Reset customer and simulate registration */
        $customerService->resetCustomerDataEndOfVisit($customer);
        $customer->setCurrentRoom($registrationRoom);
        $this->assertSame($registrationRoom, $customer->getCurrentRoom());
    }
}
