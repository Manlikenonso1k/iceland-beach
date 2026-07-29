<?php

namespace App\Livewire;

use App\Models\VendorRequest;
use App\Models\VendorSpot;
use Livewire\Component;

class VendorSpotMap extends Component
{
    // All spots for the map
    public $spots;

    // Modal state
    public bool $showModal = false;
    public ?int $selectedSpotId = null;
    public ?string $selectedSpotName = null;

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $address = '';
    public string $expectedToSell = '';

    // Success message
    public bool $submitted = false;

    protected $rules = [
        'name'           => 'required|string|max:255',
        'email'          => 'required|email|max:255',
        'address'        => 'required|string|max:500',
        'expectedToSell' => 'required|string|max:2000',
    ];

    protected $messages = [
        'name.required'           => 'Please enter your full name.',
        'email.required'          => 'Please enter your email address.',
        'email.email'             => 'Please enter a valid email address.',
        'address.required'        => 'Please enter your address.',
        'expectedToSell.required' => 'Please describe what you intend to sell.',
    ];

    public function mount()
    {
        $this->loadSpots();
    }

    public function loadSpots()
    {
        $this->spots = VendorSpot::all();
    }

    /**
     * Called when a user clicks an available spot on the map.
     */
    public function selectSpot(int $spotId)
    {
        $spot = VendorSpot::find($spotId);

        if (! $spot || $spot->status !== 'available') {
            return;
        }

        $this->selectedSpotId   = $spot->id;
        $this->selectedSpotName = $spot->name;
        $this->showModal        = true;
        $this->submitted        = false;

        // Reset form fields
        $this->reset(['name', 'email', 'address', 'expectedToSell']);
        $this->resetValidation();
    }

    /**
     * Close the modal.
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['selectedSpotId', 'selectedSpotName', 'name', 'email', 'address', 'expectedToSell']);
        $this->resetValidation();
    }

    /**
     * Submit the vendor request form.
     */
    public function submitRequest()
    {
        $this->validate();

        $spot = VendorSpot::find($this->selectedSpotId);

        // Guard: spot may have been taken while the modal was open
        if (! $spot || $spot->status !== 'available') {
            $this->addError('email', 'Sorry, this spot is no longer available.');
            return;
        }

        // Create the vendor request
        VendorRequest::create([
            'vendor_spot_id'   => $spot->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'address'          => $this->address,
            'expected_to_sell' => $this->expectedToSell,
            'status'           => 'pending',
        ]);

        // Lock the spot
        $spot->update(['status' => 'pending_approval']);

        // Reload spots so the map updates in real time
        $this->loadSpots();

        $this->submitted = true;
    }

    /**
     * Close the success message and modal.
     */
    public function closeSuccess()
    {
        $this->submitted = false;
        $this->showModal = false;
        $this->reset(['selectedSpotId', 'selectedSpotName', 'name', 'email', 'address', 'expectedToSell']);
    }

    public function render()
    {
        return view('livewire.vendor-spot-map');
    }
}
