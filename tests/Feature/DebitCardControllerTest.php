<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $userOther;


    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->userOther = User::factory()->create();
        
        Passport::actingAs($this->user);
        
        DebitCard::factory(10)
            ->create(["user_id" => $this->user->id]);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $response = $this->get('/api/debit-cards');
        $response->assertStatus(HttpResponse::HTTP_OK)
        ->assertJsonStructure([
            '*' => [
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]
        ]);

        $this->assertDatabaseHas('debit_cards', ['user_id' => $this->user->id]);

    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {

        Passport::actingAs($this->userOther);
    
        $response = $this->get('/api/debit-cards');
        $response->assertStatus(HttpResponse::HTTP_OK);
        $responseJson = json_decode($response->content(), true);
        $this->assertEmpty(
            $responseJson,
            "customer cant see a list of debit cards of other customer"
        );
        
    }

    public function testCustomerCanCreateADebitCard()
    {
        $params = ['type' => ''];
        $response = $this->post('/api/debit-cards', $params);
        $response->assertStatus(HttpResponse::HTTP_FOUND);
        $this->assertDatabaseMissing('debit_cards', ['type' => 'test123']);

        $params = ['type' => 'test123'];
        $response = $this->post('/api/debit-cards', $params);
        $response->assertStatus(HttpResponse::HTTP_CREATED)
         ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);
        $this->assertDatabaseHas('debit_cards', ['type' => 'test123']);

    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = $this->user->debitCards->first();
        $response = $this->get('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}

        DebitCard::factory(2)
        ->create(['user_id' => $this->userOther->id]);
        
        Passport::actingAs($this->userOther);
        
        $debitCard = $this->user->debitCards->first();
        $response = $this->get('/api/debit-cards/' . $debitCard->id);
        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
      

    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}

        $debitCard = $this->user->debitCards->first();
        $params = ['is_active' => ''];
        $response = $this->put('api/debit-cards/'. $debitCard->id, $params);
        $response->assertStatus(HttpResponse::HTTP_FOUND);
        $this->assertDatabaseMissing('debit_cards', ['disabled_at' => '']);

        $params = ['is_active' => true];
        $response = $this->put('api/debit-cards/'. $debitCard->id, $params);
        $response->assertStatus(HttpResponse::HTTP_OK)
        ->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active'
        ]);
        $this->assertDatabaseHas('debit_cards', ['disabled_at' => null]);
    
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = $this->user->debitCards->first();
        $params = ['is_active' => ''];
        $response = $this->put('api/debit-cards/'. $debitCard->id, $params);
        $response->assertStatus(HttpResponse::HTTP_FOUND);
        $this->assertDatabaseMissing('debit_cards', ['disabled_at' => '']);

        $params = ['is_active' => false];
        $response = $this->put('api/debit-cards/'. $debitCard->id, $params);
        $response->assertStatus(HttpResponse::HTTP_OK)
        ->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active'
        ]);
        $this->assertDatabaseMissing('debit_cards', ['id' => $debitCard->id, 'disabled_at' => null]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = $this->user->debitCards->first();
        $params = ['is_active' => '12356789'];
        $response = $this->put('api/debit-cards/'. $debitCard->id, $params);
        $response->assertStatus(HttpResponse::HTTP_FOUND);
        $this->assertDatabaseMissing('debit_cards', ['id'=> $debitCard->id, 'disabled_at' => '12356789']);

    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}

        $debitCard = $this->user->debitCards->first();
        $response = $this->delete('api/debit-cards/' . $debitCard->id);
        $response->assertStatus(HttpResponse::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('debit_cards', ['id' => $debitCard->id, 'delete_at' => null]);

    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}

        $debitCard = $this->user->debitCards->first();
        DebitCardTransaction::factory()->create(["debit_card_id" => $debitCard->id]);

        $response = $this->delete('api/debit-cards/'. $debitCard->id);

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
    }

    // Extra bonus for extra tests :)
}
