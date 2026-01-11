<?php
/**
 * Add/Edit Event Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = $event !== null;
$page_title = $is_edit ? 'Edit Event' : 'Add New Event';
?>

<div class="wrap">
    <h1><?php echo $page_title; ?></h1>

    <form method="post" action="<?php echo admin_url('admin.php?page=tsn-events'); ?>">
        <?php wp_nonce_field('tsn_event_nonce'); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo $event->id; ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="event_name">Event Name *</label></th>
                <td>
                    <input type="text" name="event_name" id="event_name" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($event->title) : ''; ?>" required>
                </td>
            </tr>

            <tr>
                <th><label for="event_description">Description</label></th>
                <td>
                    <?php 
                    $description_content = $is_edit ? $event->description : '';
                    wp_editor($description_content, 'event_description', array(
                        'textarea_name' => 'event_description',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                        'teeny' => false,
                        'tinymce' => array(
                            'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,blockquote,alignleft,aligncenter,alignright,undo,redo',
                        )
                    ));
                    ?>
                    <p class="description">Detailed description of the event. This will be displayed on the event detail page.</p>
                </td>
            </tr>

            <tr>
                <th><label for="event_excerpt">Excerpt <span style="color: #999;">(for event listing)</span></label></th>
                <td>
                    <textarea name="event_excerpt" id="event_excerpt" rows="3" class="large-text" maxlength="300" placeholder="Short description shown in event listings (max 300 characters)"><?php echo $is_edit ? esc_textarea($event->excerpt ?? '') : ''; ?></textarea>
                    <p class="description">Enter a brief description to display on the events listing page. Keep it concise!</p>
                    <p class="description"><span id="excerpt-chars">0</span>/300 characters</p>
                </td>
            </tr>

            <tr>
                <th><label for="featured_image_url">Featured Image</label></th>
                <td>
                    <div class="featured-image-wrapper">
                        <input type="hidden" name="featured_image_url" id="featured_image_url" value="<?php echo $is_edit && $event->banner_url ? esc_url($event->banner_url) : ''; ?>">
                        
                        <div id="featured-image-preview" style="margin-bottom: 15px;">
                            <?php if ($is_edit && $event->banner_url): ?>
                                <img src="<?php echo esc_url($event->banner_url); ?>" style="max-width: 300px; height: auto; display: block; border: 1px solid #ddd; border-radius: 4px;">
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" id="upload-featured-image" class="button">
                            <?php echo ($is_edit && $event->banner_url) ? 'Change Featured Image' : 'Upload Featured Image'; ?>
                        </button>
                        
                        <?php if ($is_edit && $event->banner_url): ?>
                            <button type="button" id="remove-featured-image" class="button" style="margin-left: 10px;">Remove Image</button>
                        <?php endif; ?>
                        
                        <p class="description">Recommended size: 1200x600px for best display on the events page.</p>
                    </div>
                </td>
            </tr>

            <tr>
                <th><label for="event_start_date">Start Date & Time *</label></th>
                <td>
                    <input type="datetime-local" name="event_start_date" id="event_start_date" 
                           value="<?php echo $is_edit ? date('Y-m-d\TH:i', strtotime($event->start_datetime)) : ''; ?>" required>
                </td>
            </tr>

            <tr>
                <th><label for="event_end_date">End Date & Time</label></th>
                <td>
                    <input type="datetime-local" name="event_end_date" id="event_end_date" 
                           value="<?php echo $is_edit && $event->end_datetime ? date('Y-m-d\TH:i', strtotime($event->end_datetime)) : ''; ?>">
                </td>
            </tr>

            <tr>
                <th><label for="venue_name">Venue Name</label></th>
                <td>
                    <input type="text" name="venue_name" id="venue_name" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($event->venue_name ?? '') : ''; ?>">
                </td>
            </tr>

            <tr>
                <th><label for="venue_address">Venue Address</label></th>
                <td>
                    <textarea name="venue_address" id="venue_address" rows="3" class="regular-text"><?php echo $is_edit ? esc_textarea($event->address_line1 ?? '') : ''; ?></textarea>
                </td>
            </tr>

            <tr>
                <th><label for="registration_open_date">Registration Opens</label></th>
                <td>
                    <input type="datetime-local" name="registration_open_date" id="registration_open_date" 
                           value="<?php echo $is_edit ? date('Y-m-d\TH:i', strtotime($event->reg_open_datetime)) : date('Y-m-d\TH:i'); ?>">
                </td>
            </tr>

            <tr>
                <th><label for="registration_close_date">Registration Closes</label></th>
                <td>
                    <input type="datetime-local" name="registration_close_date" id="registration_close_date" 
                           value="<?php echo $is_edit ? date('Y-m-d\TH:i', strtotime($event->reg_close_datetime)) : ''; ?>">
                </td>
            </tr>

            <tr>
                <th><label for="status">Status</label></th>
                <td>
                    <select name="status" id="status">
                        <option value="draft" <?php echo $is_edit && $event->status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo !$is_edit || $event->status === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="sold_out" <?php echo $is_edit && $event->status === 'sold_out' ? 'selected' : ''; ?>>Sold Out</option>
                        <option value="archived" <?php echo $is_edit && $event->status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="registration_mode">Registration Mode</label></th>
                <td>
                    <select name="registration_mode" id="registration_mode">
                        <option value="ticket" <?php echo !isset($event) || $event->registration_mode === 'ticket' ? 'selected' : ''; ?>>Ticketed Registration (Paid/Free Types)</option>
                        <option value="simple_rsvp" <?php echo isset($event) && $event->registration_mode === 'simple_rsvp' ? 'selected' : ''; ?>>Simple RSVP (Free, Basic Details)</option>
                    </select>
                    <p class="description">
                        <strong>Ticketed:</strong> User selects specific ticket types (e.g. Adult, Child) with prices.<br>
                        <strong>Simple RSVP:</strong> User registers for free by providing Name, Email, and Attendee details. No ticket selection.
                    </p>
                </td>
            </tr>
            <tr>
                <th><label>Event Features</label></th>
                <td>
                    <fieldset>
                        <label for="enable_ticketing">
                            <input type="checkbox" name="enable_ticketing" id="enable_ticketing" value="1" <?php echo (!isset($event) || $event->enable_ticketing) ? 'checked' : ''; ?>>
                            Enable Ticketing
                        </label>
                        <br>
                        <label for="enable_volunteering">
                            <input type="checkbox" name="enable_volunteering" id="enable_volunteering" value="1" <?php echo (isset($event) && $event->enable_volunteering) ? 'checked' : ''; ?>>
                            Enable Volunteer Registration
                        </label>
                        <br>
                        <label for="enable_donations">
                            <input type="checkbox" name="enable_donations" id="enable_donations" value="1" <?php echo (isset($event) && $event->enable_donations) ? 'checked' : ''; ?>>
                            Enable Donations
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>

        <div id="ticket-types-wrapper" style="<?php echo (!isset($event) || $event->enable_ticketing) ? '' : 'display:none;'; ?>">
            <h2>Ticket Types</h2>
            <p>Define different ticket categories with member and non-member pricing.</p>


        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Type Name</th>
                    <th>Attendees Per Ticket</th>
                    <th>Description</th>
                    <th>Member Price</th>
                    <th>Non-Member Price</th>
                    <th>Available Qty</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="ticket-types-list">
                <?php if ($ticket_types): ?>
                    <?php foreach ($ticket_types as $index => $ticket): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="ticket_types[<?php echo $index; ?>][id]" value="<?php echo $ticket->id; ?>">
                                <input type="text" name="ticket_types[<?php echo $index; ?>][type_name]" value="<?php echo esc_attr($ticket->name); ?>" class="regular-text">
                            </td>
                            <td><input type="number" name="ticket_types[<?php echo $index; ?>][attendees_per_ticket]" value="<?php echo esc_attr($ticket->attendees_per_ticket ?? 1); ?>" min="1" max="10" class="small-text" title="How many people does this ticket cover?"></td>
                            <td><input type="text" name="ticket_types[<?php echo $index; ?>][description]" value="<?php echo esc_attr($ticket->description ?? ''); ?>" class="regular-text" placeholder="Optional"></td>
                            <td><input type="number" step="0.01" name="ticket_types[<?php echo $index; ?>][member_price]" value="<?php echo $ticket->member_price; ?>" class="small-text"></td>
                            <td><input type="number" step="0.01" name="ticket_types[<?php echo $index; ?>][non_member_price]" value="<?php echo $ticket->non_member_price; ?>" class="small-text"></td>
                            <td><input type="number" name="ticket_types[<?php echo $index; ?>][available_quantity]" value="<?php echo $ticket->capacity; ?>" class="small-text"></td>
                            <td><button type="button" class="button remove-ticket-type">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td>
                            <input type="hidden" name="ticket_types[0][id]" value="0">
                            <input type="text" name="ticket_types[0][type_name]" placeholder="e.g. Adult Member" class="regular-text">
                        </td>
                        <td><input type="number" name="ticket_types[0][attendees_per_ticket]" value="1" min="1" max="10" class="small-text" title="How many people?"></td>
                        <td><input type="text" name="ticket_types[0][description]" placeholder="Description" class="regular-text"></td>
                        <td><input type="number" step="0.01" name="ticket_types[0][member_price]" placeholder="25.00" class="small-text"></td>
                        <td><input type="number" step="0.01" name="ticket_types[0][non_member_price]" placeholder="35.00" class="small-text"></td>
                        <td><input type="number" name="ticket_types[0][available_quantity]" placeholder="100" class="small-text"></td>
                        <td><button type="button" class="button remove-ticket-type">Remove</button></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p><button type="button" id="add-ticket-type" class="button">+ Add Ticket Type</button></p>

        </div><!-- #ticket-types-wrapper -->

        <p class="submit">
            <input type="submit" name="tsn_save_event" class="button button-primary" value="<?php echo $is_edit ? 'Update Event' : 'Create Event'; ?>">
            <a href="<?php echo admin_url('admin.php?page=tsn-events'); ?>" class="button">Cancel</a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Feature toggles
    // Feature toggles
    function toggleTicketTypes() {
        var ticketingEnabled = $('#enable_ticketing').is(':checked');
        var regMode = $('#registration_mode').val();
        
        if (ticketingEnabled && regMode === 'ticket') {
            $('#ticket-types-wrapper').slideDown();
        } else {
            $('#ticket-types-wrapper').slideUp();
        }
    }

    $('#enable_ticketing, #registration_mode').on('change', toggleTicketTypes);
    toggleTicketTypes(); // Init

    var ticketIndex = <?php echo $ticket_types ? count($ticket_types) : 1; ?>;
    
    // Ticket type management
    $('#add-ticket-type').on('click', function() {
        var row = '<tr>' +
            '<td><input type="hidden" name="ticket_types[' + ticketIndex + '][id]" value="0"><input type="text" name="ticket_types[' + ticketIndex + '][type_name]" class="regular-text"></td>' +
            '<td><input type="number" name="ticket_types[' + ticketIndex + '][attendees_per_ticket]" value="1" min="1" max="10" class="small-text"></td>' +
            '<td><input type="text" name="ticket_types[' + ticketIndex + '][description]" class="regular-text"></td>' +
            '<td><input type="number" step="0.01" name="ticket_types[' + ticketIndex + '][member_price]" class="small-text"></td>' +
            '<td><input type="number" step="0.01" name="ticket_types[' + ticketIndex + '][non_member_price]" class="small-text"></td>' +
            '<td><input type="number" name="ticket_types[' + ticketIndex + '][available_quantity]" class="small-text"></td>' +
            '<td><button type="button" class="button remove-ticket-type">Remove</button></td>' +
            '</tr>';
        
        $('#ticket-types-list').append(row);
        ticketIndex++;
    });
    
    $(document).on('click', '.remove-ticket-type', function() {
        $(this).closest('tr').remove();
    });
    
    // Excerpt character counter
    function updateExcerptCount() {
        var count = $('#event_excerpt').val().length;
        $('#excerpt-chars').text(count);
        if (count > 300) {
            $('#excerpt-chars').css('color', 'red');
        } else {
            $('#excerpt-chars').css('color', 'inherit');
        }
    }
    
    $('#event_excerpt').on('input', updateExcerptCount);
    updateExcerptCount(); // Initialize count
    
    // WordPress Media Uploader for Featured Image
    var mediaUploader;
    
    $('#upload-featured-image').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Extend the wp.media object
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Featured Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
       // When an image is selected, run a callback
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#featured_image_url').val(attachment.url);
            $('#featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto; display: block; border: 1px solid #ddd; border-radius: 4px;">');
            $('#upload-featured-image').text('Change Featured Image');
            
            // Show remove button if not already visible
            if ($('#remove-featured-image').length === 0) {
                $('#upload-featured-image').after('<button type="button" id="remove-featured-image" class="button" style="margin-left: 10px;">Remove Image</button>');
            }
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
    
    // Remove featured image
    $(document).on('click', '#remove-featured-image', function(e) {
        e.preventDefault();
        $('#featured_image_url').val('');
        $('#featured-image-preview').html('');
        $('#upload-featured-image').text('Upload Featured Image');
        $(this).remove();
    });
});
</script>
