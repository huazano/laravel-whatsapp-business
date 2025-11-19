<?php

namespace Database\Seeders;

use App\Models\WhatsappUser;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;

class WhatsappUsersTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test WhatsApp users with conversations
        $users = [
            [
                'phone_number' => '1234567890',
                'name' => 'Juan Pérez',
                'role' => 'premium',
                'messages' => 15,
            ],
            [
                'phone_number' => '0987654321',
                'name' => 'María García',
                'role' => 'vip',
                'messages' => 25,
            ],
            [
                'phone_number' => '5555555555',
                'name' => 'Carlos López',
                'role' => 'basic',
                'messages' => 8,
            ],
            [
                'phone_number' => '1111111111',
                'name' => 'Ana Martínez',
                'role' => 'guest',
                'messages' => 3,
            ],
            [
                'phone_number' => '9999999999',
                'name' => null,
                'role' => 'guest',
                'messages' => 5,
            ],
        ];

        foreach ($users as $userData) {
            $user = WhatsappUser::create([
                'phone_number' => $userData['phone_number'],
                'name' => $userData['name'],
                'is_active' => true,
                'last_interaction_at' => now()->subMinutes(rand(5, 120)),
            ]);

            $user->assignRole($userData['role']);

            // Create conversation
            $conversation = $user->conversations()->create([
                'status' => 'active',
                'last_message_at' => now()->subMinutes(rand(5, 120)),
            ]);

            // Create sample messages
            $messageCount = $userData['messages'];
            for ($i = 0; $i < $messageCount; $i++) {
                $isInbound = $i % 2 === 0;
                $createdAt = now()->subMinutes($messageCount - $i)->subSeconds(rand(0, 59));

                $messages = [
                    'Hola, ¿cómo estás?',
                    '¡Muy bien! ¿En qué puedo ayudarte?',
                    'Necesito información sobre sus servicios',
                    'Claro, con gusto te ayudo. Tenemos varios planes disponibles.',
                    '¿Cuáles son los precios?',
                    'Los precios varían según el plan. El básico es $10/mes.',
                    '¿Y el plan premium?',
                    'El plan premium incluye funciones avanzadas por $25/mes.',
                    'Interesante, ¿puedo probarlo?',
                    '¡Por supuesto! Te envío un enlace para comenzar.',
                    'Gracias',
                    '¡De nada! ¿Necesitas algo más?',
                    'No, eso es todo',
                    'Perfecto. ¡Que tengas un excelente día!',
                    'Igualmente, gracias',
                ];

                $content = $messages[$i % count($messages)];

                $conversation->messages()->create([
                    'direction' => $isInbound ? 'inbound' : 'outbound',
                    'type' => 'text',
                    'content' => $content,
                    'status' => $isInbound ? 'sent' : (rand(0, 2) === 0 ? 'read' : (rand(0, 1) === 0 ? 'delivered' : 'sent')),
                    'sent_at' => $createdAt,
                    'delivered_at' => !$isInbound && rand(0, 1) ? $createdAt->copy()->addSeconds(rand(1, 5)) : null,
                    'read_at' => !$isInbound && rand(0, 2) === 0 ? $createdAt->copy()->addSeconds(rand(10, 30)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        $this->command->info('Created ' . count($users) . ' test WhatsApp users with conversations');
    }
}
