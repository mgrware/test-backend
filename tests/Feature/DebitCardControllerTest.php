<?php

namespace Tests\Feature;

use App\Models\DebitCard;
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
        
        DebitCard::factory(2)
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
        $params = ["type" => ""];
        $response = $this->post('/api/debit-cards', $params);
        $response->assertStatus(HttpResponse::HTTP_FOUND);
        $this->assertDatabaseMissing('debit_cards', ['type' => 'test123']);

        $params = ["type" => "test123"];
        $response = $this->post('/api/debit-cards', $params);
        $response->assertStatus(HttpResponse::HTTP_CREATED);
        $this->assertDatabaseHas('debit_cards', ['type' => 'test123']);

    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
    }

    // Extra bonus for extra tests :)
}
