<?php

interface sendSmsInterface
{	
	public function __construct(modX $modx, array $config = []);

	/**
     * отправка СМС в сервис
     *
     * @param string $phone
     * @param string | int $code
     *
     * @return bool
     */
	public function send(string $phone, int $code);
}