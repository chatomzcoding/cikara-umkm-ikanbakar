<?php

test('halaman utama mengarah ke login', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});
