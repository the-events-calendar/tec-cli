# tribe-cli

WP-CLI tools for Modern Tribe plugins.

## Table of contents
* [Events](#events)
    * [Events generator](#events-generator)
    * [Reset events content](#reset-events)
* [Tickets](#tickets)
    * [RSVP tickets attendees generator](#rsvp-tickets-attendees-generator)
    * [Reset RSVP tickets](#reset-rsvp-tickets)
* [Docs](#docs)
    * [Build Docs](#build-docs)
    * [Import Docs](#import-docs)

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

* `post_id` - integer - attendees will be assigned to RSVP tickets on this post
* `count` - integer - the number of RSVP replies to generate
* `tickets_min` - integer - the minimum number of RSVP tickets to assign for each reply
* `tickets_max` - integer - the maximum number of RSVP tickets to assign for each reply
* `ticket_status` - string - the status of all the RSVP replies; either empty or one of "yes" or "no"
* `ticket_id` - integer - only generate attendees for a specific RSVP ticket assigned to the post

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
