<?php
namespace Repositorium\Interfaces;

interface SearchProvider
{
	public function __construct($container);
	public function searchFor($keyword);
}