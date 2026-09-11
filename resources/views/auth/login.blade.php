@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="p-4" style="background: var(--ink); border-top: 3px solid var(--brass);">
                <h1 class="font-serif fs-4 fw-semibold mb-1" style="color: #f5f3ed;">Masuk</h1>
                <p class="small mb-4" style="color: #a9b2ba;">Untuk mengajukan reservasi atau melaporkan kerusakan.</p>
                <form>
                    <div class="mb-3">
                        <label for="email" class="form-label small" style="color: #c7cdd2;">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label small" style="color: #c7cdd2;">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </form>
                <p class="small text-center mt-4 mb-0" style="color: #a9b2ba;">
                    Belum punya akun? <a href="/register" style="color: var(--brass);">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
