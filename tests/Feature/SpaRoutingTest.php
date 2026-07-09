<?php

it('serves the Vue shell for direct SPA routes', function (): void {
    $this->get('/ingresar')
        ->assertOk()
        ->assertSee('id="app"', false);
});
