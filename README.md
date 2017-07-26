# events-generator
Events Generator for WP-CLI

## Generate events command

This command will generate dummy events for TEC using the TEC API function: `tribe_create_event` 

### Arguments

* `count` - integer - The total count of events to generate

### Example

```bash
$ wp tribe-events-generate generate --count=1000
```

## Reset events command

This command will reset all events in TEC. It deletes ALL Events, Organizers, and Venues.

### Example

```bash
$ wp tribe-events-generate reset
```
