# tribe-cli

WP-CLI tools for Modern Tribe plugins.

## Table of contents
- [tribe-cli](#tribe-cli)
  - [Table of contents](#table-of-contents)
  - [Events](#events)
    - [Events Generator](#events-generator)
      - [Arguments](#arguments)
      - [Example](#example)
    - [Reset events](#reset-events)
      - [Example](#example-1)
  - [Tickets](#tickets)
    - [RSVP tickets attendees generator](#rsvp-tickets-attendees-generator)
      - [Arguments](#arguments-1)
      - [Example](#example-2)
    - [Reset RSVP tickets](#reset-rsvp-tickets)
      - [Arguments](#arguments-2)
      - [Example](#example-3)
    - [WooCommerce tickets order generator](#woocommerce-tickets-order-generator)
      - [Arguments](#arguments-3)
      - [Example](#example-4)
    - [Reset WooCommerce tickets](#reset-woocommerce-tickets)
      - [Arguments](#arguments-4)
      - [Example](#example-5)
  - [Payouts](#payouts)
    - [Payouts generator](#payouts-generator)
      - [Arguments](#arguments-5)
      - [Example](#example-6)
  - [Reset Payouts](#reset-payouts)
      - [Arguments](#arguments-6)
      - [Example](#example-7)
  - [Docs](#docs)
    - [Build Docs](#build-docs)
      - [Arguments](#arguments-7)
      - [Example](#example-8)
    - [Import Docs](#import-docs)
      - [Arguments](#arguments-8)
      - [Example](#example-9)

## Events

### Events Generator
Requires [The Events Calendar][2209-0001] installed and active version `4.5.4` or above.
This command will generate dummy events for TEC using the TEC API function: `tribe_create_event`

#### Arguments

* `count` - integer - the total count of events to generate

#### Example

```bash
$ wp tribe events-generator generate --count=1000
```

### Reset events
Requires [The Events Calendar][2209-0001] installed and active version `4.5.4` or above.
This command will reset all events in TEC. It deletes ALL Events, Organizers, and Venues.

#### Example

```bash
$ wp tribe events-generator reset
```

## Tickets

### RSVP tickets attendees generator
Requires [Event Tickets][2209-0002] installed and active version `4.5.4` or above.
This command will generate attendees for a post with RSVP tickets.

#### Arguments

* `post_id`   - integer - attendees will be assigned to tickets on this post
* `count`     - integer - the number of RSVP replies to generate
* `tickets_min` - integer - the minimum number of RSVP tickets to assign for each reply
* `tickets_max` - integer - the maximum number of RSVP tickets to assign for each reply
* `ticket_status` - string - the status of all the RSVP replies; either empty or one of "yes" or "no"
* `ticket_id` - integer - only generate attendees for a specific ticket assigned to the post

#### Example

```bash
$ wp tribe event-tickets generate-rsvp-attendees 23
$ wp tribe event-tickets generate-rsvp-attendees 23 --count=89
$ wp tribe event-tickets generate-rsvp-attendees 23 --tickets_min=3
$ wp tribe event-tickets generate-rsvp-attendees 23 --tickets_min=3 --tickets_max=10
$ wp tribe event-tickets generate-rsvp-attendees 23 --tickets_min=3 --tickets_max=10 --ticket_status=no
$ wp tribe event-tickets generate-rsvp-attendees 23 --ticket_id=89
```
### Reset RSVP tickets

This command will reset attendees for RSVP tickets assigned to a post.

#### Arguments

* `post_id` - integer - RSVP attendees will be removed from this post
* `ticket_id` - integer - only remove attendees for a specific RSVP ticket assigned to the post

#### Example

```bash
$ wp tribe event-tickets reset-rsvp-attendees 23
$ wp tribe event-tickets reset-rsvp-attendees 23 --ticket_id=89
```

### WooCommerce tickets order generator
Requires [Event Tickets Plus][2209-0007] installed and active version `4.10.5` or above.
This command will generate attendees for a post with WooCommerce tickets.

#### Arguments

* `post_id`   - integer - orders will be attached to this post.
* `count`     - integer - the number of orders to generate.
* `tickets_min` - integer - the minimum number of tickets per order.
* `tickets_max` - integer - the maximum number of tickets per order.
* `ticket_status` - string - the order  status of the orders; Must be a valid WC order status (chosen randomly if left out).
* `ticket_id` - integer - the ID of the ticket orders should be assigned to.
* `no_create_users` - flag - use available subscribers to make orders and avoid creating users.

#### Example

```bash
$ wp tribe event-tickets-plus generate-wc-orders 23
$ wp tribe event-tickets-plus generate-wc-orders 23 --count=89
$ wp tribe event-tickets-plus generate-wc-orders 23 --tickets_min=3
$ wp tribe event-tickets-plus generate-wc-orders 23 --tickets_min=3 --tickets_max=10
$ wp tribe event-tickets-plus generate-wc-orders 23 --tickets_min=3 --tickets_max=10 --ticket_status=no
$ wp tribe event-tickets-plus generate-wc-orders 23 --ticket_id=89
$ wp tribe event-tickets-plus generate-wc-orders 23 --ticket_id=89 --create_users=no
```
### Reset WooCommerce tickets

Removes all generated WC orders from a WooCommerce ticketed post.

#### Arguments

* `post_id` - integer - orders will be removed from this ticketed post.
* `ticket_id` - integer - only remove orders for this WooCommerce ticket attached to the post.

#### Example

```bash
$ wp tribe event-tickets-plus reset-wc-orders 23
$ wp tribe event-tickets-plus reset-wc-orders 23 --ticket_id=89
```

## Payouts

### Payouts generator
Requires [Community Tickets][2209-0005],  installed and active version `4.7.0` or above.
This command will generate orders for a post with tickets. Payouts will be generated from those orders if enabled.

#### Arguments

* `post_id` - integer - orders and payouts will be assigned to tickets on this post.
* `count` - integer - the number of orders to generate.
* `tickets_min` - integer - the minimum number of tickets to assign for each order.
* `tickets_max` - integer - the maximum number of tickets to assign for each order.
* `ticket_id` - integer - only generate orders for a specific ticket assigned to the post.
* * `status` - string - the order status of the orders; Must be a valid WC order status (chosen randomly if left out).

#### Example

```bash
$ wp tribe payouts generate 23
$ wp tribe payouts generate 23 --count=89
$ wp tribe payouts generate 23 --tickets_min=3
$ wp tribe payouts generate 23 --tickets_min=3 --tickets_max=10
$ wp tribe payouts generate 23 --tickets_min=3 --tickets_max=10
$ wp tribe payouts generate 23 --ticket_id=89
```

## Reset Payouts

Removes all generated WC orders and Payouts from a WooCommerce ticketed post.

#### Arguments

* `post_id` - integer - orders will be removed from this ticketed post.

#### Example

```bash
$ wp tribe payouts reset 23
```

## Docs

### Build Docs
Requires [WP Parser][2209-0003] installed and active.
This command will build a JSON file for the purposes of importing into a WP Parser-active site.

#### Arguments

* `plugin` - string - plugin directory name in wp-plugins that will be parsed
* `output` - string - file path of the resulting JSON file

#### Example

```bash
$ wp tribe doc build the-events-calendar
$ wp tribe doc build tribe-common --output=/tmp/tribe-common.json
```

### Import Docs

This command will import the output of the doc build command.

#### Arguments

* `plugin` - string - plugin directory name in wp-plugins that will be parsed
* `file` - string - file path of the JSON file used during import

#### Example

```bash
$ wp tribe doc import the-events-calendar /tmp/the-events-calendar.json --user=1
$ wp tribe doc import tribe-common /tmp/tribe-common.json --user=1
```


[2209-0001]: https://wordpress.org/plugins/the-events-calendar/
[2209-0002]: https://wordpress.org/plugins/event-tickets/
[2209-0003]: https://github.com/WordPress/phpdoc-parser
[2209-0004]: https://theeventscalendar.com/product/wordpress-event-tickets-plus/
[2209-0005]: https://theeventscalendar.com/product/wordpress-community-events/
[2209-0006]: https://theeventscalendar.com/product/community-tickets/
[2209-0007]: https://theeventscalendar.com/product/wordpress-event-tickets-plus/
