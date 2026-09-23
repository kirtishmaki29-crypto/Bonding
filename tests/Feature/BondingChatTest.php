<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BondingChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test landing / login page renders with Moon + Ocean + Galaxy aesthetic elements.
     */
    public function test_landing_page_renders_with_moon_ocean_galaxy_elements(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Bonding');
        $response->assertSee('Connect under the Stars');
        $response->assertSee('Continue with Google');
        $response->assertSee('🌙');
        $response->assertSee('🌊');
    }

    /**
     * Test unauthenticated guests cannot access private chat areas.
     */
    public function test_unauthenticated_user_cannot_access_chat_or_api(): void
    {
        $this->get('/chat')->assertRedirect('/login');
        $this->getJson('/api/conversations')->assertStatus(401);
    }

    /**
     * Test authenticated user can access the chat view.
     */
    public function test_authenticated_user_can_access_chat(): void
    {
        $user = User::factory()->create([
            'name' => 'Starlight Wanderer',
            'email' => 'starlight@bonding.space',
        ]);

        $response = $this->actingAs($user)->get('/chat');

        $response->assertStatus(200);
        $response->assertSee('Starlight Wanderer');
        $response->assertSee('💬 Chats');
        $response->assertSee('Discover People');
    }

    /**
     * Test fetching conversations for the authenticated user.
     */
    public function test_user_can_fetch_conversations(): void
    {
        $userA = User::factory()->create(['name' => 'User Alpha']);
        $userB = User::factory()->create(['name' => 'User Beta']);

        $conversation = Conversation::findOrCreateBetween($userA->id, $userB->id);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userB->id,
            'body' => 'Greetings across the cosmic tides!',
            'is_read' => false,
        ]);

        $response = $this->actingAs($userA)->getJson('/api/conversations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'conversations' => [
                '*' => [
                    'id',
                    'other_user' => ['id', 'name', 'avatar'],
                    'latest_message' => ['body', 'is_sender', 'is_read'],
                    'unread_count',
                ],
            ],
        ]);
        $response->assertJsonFragment(['name' => 'User Beta']);
    }

    /**
     * Test sending a message in a conversation.
     */
    public function test_user_can_send_message(): void
    {
        $userA = User::factory()->create(['name' => 'User One']);
        $userB = User::factory()->create(['name' => 'User Two']);

        $conversation = Conversation::findOrCreateBetween($userA->id, $userB->id);

        $response = $this->actingAs($userA)->postJson("/api/conversations/{$conversation->id}/messages", [
            'body' => 'Hello beneath the moonlit sky.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message.body', 'Hello beneath the moonlit sky.');
        $response->assertJsonPath('message.is_sender', true);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $userA->id,
            'body' => 'Hello beneath the moonlit sky.',
        ]);
    }

    /**
     * Security authorization test: user cannot access or send to conversations they do not participate in.
     */
    public function test_user_cannot_access_unauthorized_conversation(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);
        $intruder = User::factory()->create(['name' => 'Intruder']);

        $conversation = Conversation::findOrCreateBetween($userA->id, $userB->id);

        // Intruder tries to read messages
        $this->actingAs($intruder)
            ->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertStatus(403);

        // Intruder tries to send a message
        $this->actingAs($intruder)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'body' => 'I should not be able to send this',
            ])
            ->assertStatus(403);
    }

    /**
     * Test discovering people and initiating a new 1-to-1 conversation.
     */
    public function test_user_can_discover_and_start_conversation(): void
    {
        $userA = User::factory()->create(['name' => 'Explorer One']);
        $userB = User::factory()->create(['name' => 'Explorer Two', 'headline' => 'Quiet observer']);

        // Discover users list
        $response = $this->actingAs($userA)->getJson('/api/users');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Explorer Two']);

        // Start conversation
        $startResponse = $this->actingAs($userA)->postJson("/api/users/{$userB->id}/start");
        $startResponse->assertStatus(200);
        $startResponse->assertJsonPath('success', true);
        $this->assertNotNull($startResponse->json('conversation_id'));
    }
}
