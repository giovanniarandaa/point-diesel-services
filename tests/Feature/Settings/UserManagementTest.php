<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('encargado', 'web');
});

// Guest guards
test('guests cannot access users index', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('guests cannot access create user page', function () {
    $this->get(route('users.create'))->assertRedirect(route('login'));
});

test('guests cannot store users', function () {
    $this->post(route('users.store'), [])->assertRedirect(route('login'));
});

test('guests cannot access edit user page', function () {
    $user = User::factory()->create();
    $this->get(route('users.edit', $user))->assertRedirect(route('login'));
});

test('guests cannot update users', function () {
    $user = User::factory()->create();
    $this->patch(route('users.update', $user), [])->assertRedirect(route('login'));
});

test('guests cannot delete users', function () {
    $user = User::factory()->create();
    $this->delete(route('users.destroy', $user))->assertRedirect(route('login'));
});

// Encargado guards
test('encargado cannot access users index', function () {
    $user = User::factory()->create();
    $user->assignRole('encargado');

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('encargado cannot store users', function () {
    $user = User::factory()->create();
    $user->assignRole('encargado');

    $this->actingAs($user)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'encargado',
        ])
        ->assertForbidden();
});

// Admin CRUD
test('admin can list users with roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->create()->assignRole('encargado');

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk();
});

test('admin can view create user form', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertOk();
});

test('admin can create user with role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'encargado',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $newUser = User::query()->where('email', 'manager@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->hasRole('encargado'))->toBeTrue();
});

test('admin can view edit user form', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();
    $targetUser->assignRole('encargado');

    $this->actingAs($admin)
        ->get(route('users.edit', $targetUser))
        ->assertOk();
});

test('admin can update user name email and role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $targetUser->assignRole('encargado');

    $this->actingAs($admin)
        ->patch(route('users.update', $targetUser), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'admin',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $targetUser->refresh();
    expect($targetUser->name)->toBe('Updated Name');
    expect($targetUser->email)->toBe('updated@example.com');
    expect($targetUser->hasRole('admin'))->toBeTrue();
});

test('admin can update user password', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();
    $targetUser->assignRole('encargado');
    $oldPassword = $targetUser->password;

    $this->actingAs($admin)
        ->patch(route('users.update', $targetUser), [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'role' => 'encargado',
        ])
        ->assertRedirect(route('users.index'));

    $targetUser->refresh();
    expect($targetUser->password)->not->toBe($oldPassword);
});

test('password is optional on update', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();
    $targetUser->assignRole('encargado');
    $oldPassword = $targetUser->password;

    $this->actingAs($admin)
        ->patch(route('users.update', $targetUser), [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => 'encargado',
        ])
        ->assertRedirect(route('users.index'));

    $targetUser->refresh();
    expect($targetUser->password)->toBe($oldPassword);
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->delete(route('users.destroy', $admin))
        ->assertRedirect()
        ->assertSessionHasErrors('delete');

    expect(User::query()->find($admin->id))->not->toBeNull();
});

test('admin can delete another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();
    $targetUser->assignRole('encargado');

    $this->actingAs($admin)
        ->delete(route('users.destroy', $targetUser))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    expect(User::query()->find($targetUser->id))->toBeNull();
});

// Validation
test('email must be unique on create', function () {
    $admin = User::factory()->create(['email' => 'taken@example.com']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'encargado',
        ])
        ->assertSessionHasErrors('email');
});

test('email must be unique except self on update', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    $otherUser = User::factory()->create(['email' => 'other@example.com']);
    $otherUser->assignRole('encargado');

    $targetUser = User::factory()->create(['email' => 'target@example.com']);
    $targetUser->assignRole('encargado');

    // Can keep own email
    $this->actingAs($admin)
        ->patch(route('users.update', $targetUser), [
            'name' => 'Updated',
            'email' => 'target@example.com',
            'role' => 'encargado',
        ])
        ->assertRedirect(route('users.index'));

    // Cannot use another user's email
    $this->actingAs($admin)
        ->patch(route('users.update', $targetUser), [
            'name' => 'Updated',
            'email' => 'other@example.com',
            'role' => 'encargado',
        ])
        ->assertSessionHasErrors('email');
});

test('password is required on create', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'role' => 'encargado',
        ])
        ->assertSessionHasErrors('password');
});

test('role must be valid', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ])
        ->assertSessionHasErrors('role');
});
