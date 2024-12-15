<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class App extends Component
{
    public function __construct(public ?string $pageTitle = null)
    {
        //
    }

    public function render(): View
    {
        return view('components.app');
    }
}
