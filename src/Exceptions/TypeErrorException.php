<?php
namespace Simon\Upload\Exceptions;
class TypeErrorException extends \RuntimeException
{

	public function __construct($filename,$type) 
	{
		parent::__construct(sprintf("%s file %s is error ",$filename,$type));
	}
	
}