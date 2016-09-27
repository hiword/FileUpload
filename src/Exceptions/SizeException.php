<?php
namespace Simon\Upload\Exceptions;

class SizeException extends \RuntimeException
{
	
	public function __construct($filename)
	{
		parent::__construct(sprintf('File “%s” size exceeds the limit ',$filename));
	}
	
}