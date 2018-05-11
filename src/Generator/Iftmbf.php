<?php
namespace EDI\Generator;

class Iftmbf extends Message
{
    
    private $messageSender;
    private $messageSenderInformation;
    private $dtmSend;

    private $transportRequirements;
    private $freeTextInstructions;
    private $cargoNature;

    private $placeOfReceipt;
    private $placeOfDelivery;
    private $bookingOffice;

    private $contractNumber;
    private $shipmentReference;

    private $vessel;
    private $pol;
    private $pod;

    private $bookingParty;
    private $carrier;
    private $forwarder;
    private $consignor;

    private $containers;

    public function __construct($messageID = null, $identifier = 'IFTMBF', $version = 'D', $release = '00B', $controllingAgency = 'UN', $association = '2.0')
    {
        parent::__construct($identifier, $version, $release, $controllingAgency, $messageID, $association);

        $this->dtmSend = self::dtmSegment(137, date('YmdHi'));

        $this->containers = [];
    }

    public function setSender($name, $email)
    {
        $this->messageSender = ['CTA', 'IC', ['', $name]];
        $this->messageSenderInformation = ['COM', [$email, 'EM']];
        return $this;
    }

    /**
     * Transport type requested
     * $tsr DE 4065
     */
    public function setTransportRequirements($tsr)
    {
        $this->transportRequirements = ['TSR', 27];
        return $this;
    }

    /**
     * Free text instructions
     * $ftx Max 512*5 chars
     */
    public function setFreeTextInstructions($ftx)
    {
        $this->freeTextInstructions = ['FTX', 'AAI', '', '', str_split($ftx, 512)];
        return $this;
    }

    /**
     * Cargo nature
     * $cargo DE 7085
     */
    public function setCargoNature($cargo)
    {
        $this->cargoNature = ['GDS', $cargo];
        return $this;
    }

    public function setPlaceOfReceipt($porLocode)
    {
        $this->placeOfReceipt = self::locSegment(88, [$porLocode, 181, 6]);
        return $this;
    }

    public function setPlaceOfDelivery($podLocode)
    {
        $this->placeOfDelivery = self::locSegment(7, [$podLocode, 181, 6]);
        return $this;
    }

    public function setBookingOffice($bkgLocode)
    {
        $this->bookingOffice = self::locSegment(197, [$bkgLocode, 181, 6]);
        return $this;
    }

    public function setContractNumber($ctNumber)
    {
        $this->contractNumber = self::rffSegment('CT', $ctNumber);
        return $this;
    }

    public function setShipmentReference($siNumber)
    {
        $this->shipmentReference = self::rffSegment('SI', $siNumber);
        return $this;
    }

    /*
     * Vessel call information
     *
     * $extVoyage Common voyage reference
     * $scac SCAC code for the liner
     * $imonumber Vessel IMO number (7 digits)
     * $vslName Vessel name
     */
    public function setVessel($extVoyage, $scac, $vslName, $imonumber)
    {
        $this->vessel = self::tdtSegment(20, $extVoyage, 1, 8, [$scac, 172, 182], '', '', [$imonumber, 146, 11, $vslName]);
        return $this;
    }

    /*
     * Port of Loading
     *
     */
    public function setPOL($loc)
    {
        $this->pol = self::locSegment(9, [$loc, 139, 6]);
        return $this;
    }

    /*
     * Port of Discharge
     *
     */
    public function setPOD($loc)
    {
        $this->pod = self::locSegment(11, [$loc, 139, 6]);
        return $this;
    }

    /*
     * Booking party
     * $code Code identifying the booking party
     * $name Company name (max 70 chars)
     * $address Address (max 105 chars)
     * $postalCode ZIP Code
     */
    public function setBookingParty($code, $name, $address, $postalCode)
    {
        $name = str_split($name, 35);
        $address = str_split($address, 35);

        $this->bookingParty = ['NAD', 'ZZZ', [$code, 160, 'ZZZ'], array_merge($name, $address), '', '', '', '', $postalCode];
        return $this;
    }

    /*
     * $scac SCAC code for the liner
     */
    public function setCarrier($scac)
    {
        $this->carrier = ['NAD', 'CA', [$scac, 160, 'ZZZ']];
        return $this;
    }

    public function setForwarder($code, $name, $address, $postalCode)
    {
        $name = str_split($name, 35);
        $address = str_split($address, 35);

        $this->forwarder = ['NAD', 'FW', [$code, 160, 'ZZZ'], array_merge($name, $address), '', '', '', '', $postalCode];
        return $this;
    }

    public function setConsignor($code, $name, $address, $postalCode)
    {
        $name = str_split($name, 35);
        $address = str_split($address, 35);

        $this->consignor = ['NAD', 'CZ', [$code, 160, 'ZZZ'], array_merge($name, $address), '', '', '', '', $postalCode];
        return $this;
    }

    public function addContainer(Iftmbf\Container $container)
    {
        $this->containers[] = $container;
        return $this;
    }
    
    public function compose($msgStatus = 5, $documentCode = 335)
    {
        $this->messageContent = [
            ['BGM', $documentCode, $this->messageID, $msgStatus]
        ];
        $this->messageContent[] = $this->messageSender;
        $this->messageContent[] = $this->messageSenderInformation;
        $this->messageContent[] = $this->dtmSend;

        $this->messageContent[] = $this->transportRequirements;
        $this->messageContent[] = $this->freeTextInstructions;
        $this->messageContent[] = $this->cargoNature;

        $this->messageContent[] = $this->placeOfDelivery;
        $this->messageContent[] = $this->placeOfReceipt;
        $this->messageContent[] = $this->bookingOffice;

        $this->messageContent[] = $this->contractNumber;
        $this->messageContent[] = $this->shipmentReference;

        $this->messageContent[] = $this->vessel;
        $this->messageContent[] = $this->pol;
        $this->messageContent[] = $this->pod;

        $this->messageContent[] = $this->bookingParty;
        $this->messageContent[] = $this->carrier;
        $this->messageContent[] = $this->forwarder;
        $this->messageContent[] = $this->consignor;

        //$this->messageContent[] = ['GID', 1];

        foreach ($this->containers as $cntr) {
            $content = $cntr->composeGoods();
            foreach ($content as $segment) {
                $this->messageContent[] = $segment;
            }
        }

        foreach ($this->containers as $cntr) {
            $content = $cntr->composeEquipment();
            foreach ($content as $segment) {
                $this->messageContent[] = $segment;
            }
        }

        parent::compose();
        return $this;
    }
}