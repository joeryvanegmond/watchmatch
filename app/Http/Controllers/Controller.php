<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function Home()
    {
        return view('home');
    }
}
