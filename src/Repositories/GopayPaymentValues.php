<?php

namespace Crm\GoPayModule\Repositories;

class GopayPaymentValues
{
    private $values = [];

    public function setTransactionId($transactionId): self
    {
        $this->values['transaction_id'] = $transactionId;
        return $this;
    }

    public function setTransactionReference($transactionReference): self
    {
        $this->values['transaction_reference'] = $transactionReference;
        return $this;
    }

    public function setState($state): self
    {
        $this->values['state'] = $state;
        return $this;
    }

    public function setSubState($subState): self
    {
        $this->values['sub_state'] = $subState;
        return $this;
    }

    public function setPaymentInstrument($paymentInstrument): self
    {
        $this->values['payment_instrument'] = $paymentInstrument;
        return $this;
    }

    public function setCardNumber($cardNumber): self
    {
        $this->values['card_number'] = $cardNumber;
        return $this;
    }

    public function setCardExpiration($cardExpiration): self
    {
        $this->values['card_expiration'] = $cardExpiration;
        return $this;
    }

    public function setCardBrand($cardBrand): self
    {
        $this->values['card_brand'] = $cardBrand;
        return $this;
    }

    public function setIssuerCountry($issuerCountry): self
    {
        $this->values['issuer_country'] = $issuerCountry;
        return $this;
    }

    public function setIssuerBank($issuerBank): self
    {
        $this->values['issuer_bank'] = $issuerBank;
        return $this;
    }

    public function setAccountNumber($accountNumber): self
    {
        $this->values['account_number'] = $accountNumber;
        return $this;
    }

    public function setBankCode($bankCode): self
    {
        $this->values['bank_code'] = $bankCode;
        return $this;
    }

    public function setAccountName($accountName): self
    {
        $this->values['account_name'] = $accountName;
        return $this;
    }

    public function setContactEmail($email): self
    {
        $this->values['contact_email'] = $email;
        return $this;
    }

    public function setContactCountryCode($code): self
    {
        $this->values['contact_country_code'] = $code;
        return $this;
    }

    public function setUrl($url): self
    {
        $this->values['url'] = $url;
        return $this;
    }

    public function setRecurrenceCycle($recurrenceCycle): self
    {
        $this->values['recurrence_cycle'] = $recurrenceCycle;
        return $this;
    }

    public function setRecurrenceDateTo($recurrenceDateTo): self
    {
        $this->values['recurrence_date_to'] = $recurrenceDateTo;
        return $this;
    }

    public function setRecurrenceState($recurrenceState): self
    {
        $this->values['recurrence_state'] = $recurrenceState;
        return $this;
    }

    public function setEetFik($fik): self
    {
        $this->values['eet_fik'] = $fik;
        return $this;
    }

    public function setEetBkp($bkp): self
    {
        $this->values['eet_bkp'] = $bkp;
        return $this;
    }

    public function setEetPkp($pkp): self
    {
        $this->values['eet_pkp'] = $pkp;
        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
