<?php

namespace Omnipay\Core\Sign;

interface SignInterface 
{
	public function sign($input, $secret);
}