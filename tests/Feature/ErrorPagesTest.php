<?php

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

describe('a form left open too long', function () {
    beforeEach(function () {
        // Tests skip the token check, so the route fails the way an expired form does.
        Route::middleware('web')->post('en/test-expired-form', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
        Route::middleware('web')->post('admin/test-expired-form', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
    });

    it('goes back to the form with what was typed and a note, instead of "page expired"', function () {
        $this->from(route('contact.show', ['locale' => 'en']))
            ->post('/en/test-expired-form', ['name' => 'Fatima', 'message' => 'Hello', 'password' => 'secret-123', '_token' => 'old'])
            ->assertRedirect(route('contact.show', ['locale' => 'en']))
            ->assertSessionHas('page_expired', true)
            ->assertSessionHasInput('name', 'Fatima')
            ->assertSessionHasInput('message', 'Hello')
            ->assertSessionMissing('_old_input.password');

        $this->get(route('contact.show', ['locale' => 'ar']))
            ->assertSee('بقيت هذه الصفحة مفتوحة لفترة');
    });

    it('never sends the shopper to another site', function () {
        $this->from('https://example.com/form')
            ->post('/en/test-expired-form')
            ->assertRedirect(route('locale.redirect'));
    });

    it('explains itself on admin forms', function () {
        $this->from(route('admin.login'))
            ->post('/admin/test-expired-form', ['email' => 'owner@example.com'])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('error', 'This page was open for a while, so it was refreshed. Please try again.')
            ->assertSessionHasInput('email', 'owner@example.com');

        $this->get(route('admin.login'))->assertSee('This page was open for a while');
    });

    it('still answers the slide-in bag with an error it can read', function () {
        $this->postJson('/en/test-expired-form')->assertStatus(419);
    });
});

describe('server problems', function () {
    it('shows a page in both languages without needing the database', function () {
        config(['app.debug' => false]);
        Route::get('test-server-error', fn () => throw new RuntimeException('Database is down.'));

        $this->get('/test-server-error')
            ->assertStatus(500)
            ->assertSee('Something went wrong')
            ->assertSee('حدث خطأ ما')
            ->assertDontSee('Database is down.');
    });

    it('has pages for maintenance and too many tries', function (string $view, string $english, string $arabic) {
        $html = view($view)->render();

        expect($html)->toContain($english)->toContain($arabic);
    })->with([
        'maintenance' => ['errors.503', 'right back', 'سنعود قريباً'],
        'too many tries' => ['errors.429', 'Too many tries', 'محاولات كثيرة'],
    ]);
});
