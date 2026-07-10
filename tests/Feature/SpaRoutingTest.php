<?php

it('serves the Vue shell for direct SPA routes', function (): void {
    $this->get('/ingresar')
        ->assertOk()
        ->assertSee('id="app"', false);
});

it('does not let the SPA fallback mask unknown API routes', function (): void {
    $this->getJson('/api/v1/does-not-exist')->assertNotFound();
});
