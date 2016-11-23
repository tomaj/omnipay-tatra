<?php

namespace Omnipay\TatraPay\Sign;

interface SignInterface 
{
	public function sign($input, $secret);
}