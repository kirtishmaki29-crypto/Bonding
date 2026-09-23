<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BondingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Demo / Explorer account for quick preview
        $explorer = User::updateOrCreate(
            ['email' => 'explorer@bonding.space'],
            [
                'name' => 'Celestial Explorer',
                'password' => Hash::make('bonding123'),
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=256&h=256&q=80',
                'headline' => 'Wandering through starlight and ocean echoes',
                'is_online' => true,
                'last_active_at' => Carbon::now(),
            ]
        );

        // 2. Persona 1: Luna Starling
        $luna = User::updateOrCreate(
            ['email' => 'luna@bonding.space'],
            [
                'name' => 'Luna Starling',
                'password' => Hash::make('bonding123'),
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=256&h=256&q=80',
                'headline' => 'Stargazer & deep thinker · finding peace beside nighttime tides',
                'is_online' => true,
                'last_active_at' => Carbon::now()->subMinutes(2),
            ]
        );

        // 3. Persona 2: Orion Reed
        $orion = User::updateOrCreate(
            ['email' => 'orion@bonding.space'],
            [
                'name' => 'Orion Reed',
                'password' => Hash::make('bonding123'),
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=256&h=256&q=80',
                'headline' => 'Oceanographer & night diver · exploring bioluminescence in deep waters',
                'is_online' => true,
                'last_active_at' => Carbon::now()->subMinutes(5),
            ]
        );

        // 4. Persona 3: Marina Deep
        $marina = User::updateOrCreate(
            ['email' => 'marina@bonding.space'],
            [
                'name' => 'Marina Deep',
                'password' => Hash::make('bonding123'),
                'avatar' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=256&h=256&q=80',
                'headline' => 'Ambient sound artist · recording coastal night winds and ocean currents',
                'is_online' => false,
                'last_active_at' => Carbon::now()->subHours(3),
            ]
        );

        // 5. Persona 4: Caspian Drake
        $caspian = User::updateOrCreate(
            ['email' => 'caspian@bonding.space'],
            [
                'name' => 'Caspian Drake',
                'password' => Hash::make('bonding123'),
                'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=256&h=256&q=80',
                'headline' => 'Astrophotographer · capturing the Andromeda galaxy over open seas',
                'is_online' => true,
                'last_active_at' => Carbon::now()->subMinutes(12),
            ]
        );

        // 6. Persona 5: Nova Chen
        $nova = User::updateOrCreate(
            ['email' => 'nova@bonding.space'],
            [
                'name' => 'Nova Chen',
                'password' => Hash::make('bonding123'),
                'avatar' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=256&h=256&q=80',
                'headline' => 'Cosmic philosopher & poet · weaving words into midnight constellations',
                'is_online' => false,
                'last_active_at' => Carbon::now()->subDay(),
            ]
        );

        // Create initial conversations and friendly dialogue with Explorer
        // Conversation 1: Luna Starling & Explorer
        $c1 = Conversation::findOrCreateBetween($explorer->id, $luna->id);
        $c1->messages()->delete();

        Message::create([
            'conversation_id' => $c1->id,
            'sender_id' => $luna->id,
            'body' => 'Have you seen the moon tonight? It has this breathtaking silver aura reflecting over the dark water.',
            'is_read' => true,
            'read_at' => Carbon::now()->subHours(2),
            'created_at' => Carbon::now()->subHours(2),
        ]);

        Message::create([
            'conversation_id' => $c1->id,
            'sender_id' => $explorer->id,
            'body' => 'Yes! The tide is completely quiet, and the stars are so crisp. It feels like time slowed down.',
            'is_read' => true,
            'read_at' => Carbon::now()->subHours(1)->subMinutes(50),
            'created_at' => Carbon::now()->subHours(1)->subMinutes(50),
        ]);

        $msgLast1 = Message::create([
            'conversation_id' => $c1->id,
            'sender_id' => $luna->id,
            'body' => 'That quietness is rare. It is like the entire galaxy is listening with us. How was your day?',
            'is_read' => false,
            'created_at' => Carbon::now()->subMinutes(18),
        ]);
        $c1->update(['last_message_at' => $msgLast1->created_at]);

        // Conversation 2: Orion Reed & Explorer
        $c2 = Conversation::findOrCreateBetween($explorer->id, $orion->id);
        $c2->messages()->delete();

        Message::create([
            'conversation_id' => $c2->id,
            'sender_id' => $orion->id,
            'body' => 'We were just finishing a nocturnal dive near the trench. The bioluminescent plankton looked like underwater constellations.',
            'is_read' => true,
            'read_at' => Carbon::now()->subHours(4),
            'created_at' => Carbon::now()->subHours(4),
        ]);

        Message::create([
            'conversation_id' => $c2->id,
            'sender_id' => $explorer->id,
            'body' => 'That must be magical. The ocean depths have always felt as endless as deep space.',
            'is_read' => true,
            'read_at' => Carbon::now()->subHours(3)->subMinutes(40),
            'created_at' => Carbon::now()->subHours(3)->subMinutes(40),
        ]);

        $msgLast2 = Message::create([
            'conversation_id' => $c2->id,
            'sender_id' => $orion->id,
            'body' => 'They truly are two sides of the same mystery. I will share some deep-sea captures soon!',
            'is_read' => true,
            'read_at' => Carbon::now()->subHours(3),
            'created_at' => Carbon::now()->subHours(3),
        ]);
        $c2->update(['last_message_at' => $msgLast2->created_at]);

        // Conversation 3: Caspian Drake & Explorer
        $c3 = Conversation::findOrCreateBetween($explorer->id, $caspian->id);
        $c3->messages()->delete();

        $msgLast3 = Message::create([
            'conversation_id' => $c3->id,
            'sender_id' => $caspian->id,
            'body' => 'Setting up a 60-second long exposure on the shoreline right now. The Milky Way core is rising right above the ocean horizon.',
            'is_read' => false,
            'created_at' => Carbon::now()->subMinutes(45),
        ]);
        $c3->update(['last_message_at' => $msgLast3->created_at]);
    }
}
