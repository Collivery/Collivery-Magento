<?php

namespace MDS\Collivery\Exceptions;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class NoShippingException extends LocalizedException
{

    /**
     * NoShippingException constructor.
     *
     * @param Phrase|null     $phrase
     * @param \Exception|null $cause
     * @param int             $code
     */
    public function __construct(Phrase $phrase = null, \Exception $cause = null, $code = 0)
    {
        if ($phrase === null) {
            $phrase = new Phrase('Collivery.net shipping estimates not showing, please contact the shop owner');
        }
        parent::__construct($phrase, $cause, $code);
    }
}
