<?php



namespace App\Notifications;



use App\Models\Order;

use Illuminate\Bus\Queueable;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

use Illuminate\Notifications\Messages\BroadcastMessage;

use Illuminate\Notifications\Messages\MailMessage;

use Illuminate\Notifications\Notification;



class OrderConfirmedNotification extends Notification implements ShouldBroadcastNow

{

    use Queueable;



    public function __construct(public Order $order) {}



    public function via(object $notifiable): array

    {

        return ['mail', 'database', 'broadcast'];

    }



    public function toMail(object $notifiable): MailMessage

    {

        $this->order->loadMissing('package');



        $mail = (new MailMessage)

            ->subject('Aretia — Order Confirmed')

            ->line('Your order '.$this->order->reference.' has been confirmed.')

            ->line('Package: '.$this->order->package->name);



        if ($this->order->due_date) {

            $mail->line('Due date: '.$this->order->due_date->format('d M Y'));

        } else {

            $mail->line('Due date: Not set yet — you will be notified when it is scheduled.');

        }



        return $mail->action('View Order', route('client.orders.show', $this->order));

    }



    public function toArray(object $notifiable): array

    {

        $this->order->loadMissing('package');



        $message = 'Order '.$this->order->reference.' confirmed.';

        if ($this->order->due_date) {

            $message .= ' Due: '.$this->order->due_date->format('d M Y').'.';

        } else {

            $message .= ' Due date not set yet.';

        }



        return [

            'title' => 'Order confirmed',

            'message' => $message,

            'url' => route('client.orders.show', $this->order),

            'type' => 'order_confirmed',

        ];

    }



    public function toBroadcast(object $notifiable): BroadcastMessage

    {

        return new BroadcastMessage($this->toArray($notifiable));

    }



    public function broadcastType(): string

    {

        return 'order.confirmed';

    }

}

