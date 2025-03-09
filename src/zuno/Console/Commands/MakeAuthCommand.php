<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeAuthCommand extends Command
{
    protected static $defaultName = 'make:auth';

    protected function configure()
    {
        $this
            ->setName('make:auth')
            ->setDescription('Scaffolds authentication system (controllers, views, and routes).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Define paths
        $basePath = base_path();
        $controllersPath = "{$basePath}/app/Http/Controllers/Auth/";
        $homeControllersPath = "{$basePath}/app/Http/Controllers/";
        $viewsPath = "{$basePath}/resources/views/auth/";
        $layoutsPath = "{$basePath}/resources/views/layouts/";
        $homePath = "{$basePath}/resources/views/";
        $routesPath = "{$basePath}/routes/web.php";

        // Create necessary directories
        foreach ([$controllersPath, $viewsPath, $layoutsPath, $homePath] as $path) {
            if (!is_dir($path)) mkdir($path, 0755, true);
        }
        // Generate controllers
        $this->createFile($controllersPath . "LoginController.php", $this->getLoginController());
        $this->createFile($controllersPath . "RegisterController.php", $this->getRegisterController());
        $this->createFile($homeControllersPath . "HomeController.php", $this->getHomeController());

        // Generate views
        $this->createFile($viewsPath . "login.blade.php", $this->getLoginView());
        $this->createFile($viewsPath . "register.blade.php", $this->getRegisterView());
        $this->createFile($layoutsPath . "app.blade.php", $this->getAppLayout());
        $this->createFile($homePath . "home.blade.php", $this->getHomeView());

        // Append routes
        $this->appendRoutes($routesPath);

        $output->writeln('<info>Authentication scaffolding generated successfully.</info>');
        return Command::SUCCESS;
    }

    private function createFile(string $path, string $content)
    {
        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }
    }

    private function appendRoutes(string $routesPath)
    {
        $authRoutes = <<<EOT
// Auth Routes
Route::get('/home', [\App\Http\Controllers\HomeController::class, 'index'])->name('dashboard')->middleware('auth');

Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'index'])->name('register')->middleware('guest');
Route::post('/user/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register.create')->middleware('guest');

EOT;

        if (file_exists($routesPath)) {
            file_put_contents($routesPath, $authRoutes, FILE_APPEND);
        }
    }

    private function getAppLayout(): string
    {
        return <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">Zuno</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @guest
                        <li class="nav-item"><a class="nav-link text-white me-3" href="{{ route('login') }}">Sign In</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="{{ route('register') }}">Register</a></li>
                    @else
                        <li class="nav-item"><a class="nav-link text-white me-3" href="{{ route('dashboard') }}">Home</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                {{ Zuno\Auth\Security\Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile', Zuno\Auth\Security\Auth::user()->username) }}">Profile</a></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">@csrf
                                        <button type="submit" class="dropdown-item">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center mt-4"><p>&copy; {{ date('Y') }} All rights reserved.</p></footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
EOT;
    }

    private function getHomeView(): string
    {
        return <<<EOT
@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Dashboard</h5>
        </div>
        <div class="card-body">
            <p class="fw-bold fs-5">
                You are logged in as
                <span class="text-success fw-bold">{{ Zuno\Auth\Security\Auth::user()->email }}</span>
            </p>
        </div>
    </div>
@endsection
EOT;
    }

    private function getLoginController(): string
    {
        return <<<EOT
<?php

namespace App\Http\Controllers\Auth;

use Zuno\Http\Request;
use Zuno\Auth\Security\Auth;
use App\Models\User;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request \$request)
    {
        \$request->sanitize([
            'email' => 'required|email|min:2|max:100',
            'password' => 'required|min:2|max:20'
        ]);

        \$user = User::where('email', \$request->email)->first();

        if (\$user) {
            if (Auth::try(\$request->passed())) {
                flash()->message('success', 'You are logged in');
                return redirect()->to('/home');
            }
            flash()->message('error', 'Email or password is incorrect');
            return redirect()->back();
        }

        flash()->message('error', 'User does not exist');
        return redirect()->back();
    }

    public function logout()
    {
        Auth::logout();
        flash()->message('success', 'You are successfully logged out');
        return redirect()->to('/login');
    }
}
EOT;
    }

    private function getRegisterController(): string
    {
        return <<<EOT
<?php

namespace App\Http\Controllers\Auth;

use Zuno\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;

class RegisterController extends Controller
{
    public function index()
    {
        return view('auth.register');
    }

    public function register(Request \$request)
    {
        \$request->sanitize([
            'email' => 'required|email|unique:users|min:2|max:100',
            'password' => 'required|min:2|max:20',
            'username' => 'required|unique:users|min:2|max:100',
            'name' => 'required|min:2|max:20'
        ]);

        \$user = User::create(\$request->passed());

        if (\$user) {
            flash()->message('success', 'User created successfully');
            return redirect()->to('/login');
        }

        return redirect()->back();
    }
}
EOT;
    }

    private function getHomeController()
    {
        return <<<EOT
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function home()
    {
       return view('home');
    }
}
EOT;
    }

    private function getLoginView(): string
    {
        return <<<EOT
@extends('layouts.app')
@section('title', 'Login')
@section('content')
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Login</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </form>
        </div>
    </div>
@endsection
EOT;
    }

    private function getRegisterView(): string
    {
        return <<<EOT
@extends('layouts.app')
@section('title', 'Register')
@section('content')
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Register</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('register.create') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Register</button>
            </form>
        </div>
    </div>
@endsection
EOT;
    }
}
