$Field
<% if $Message %><span class="message $MessageType">$Message</span><% end_if %>

<% if $ReCAPTCHAv3BadgeDisplay=='field' %>
    <% include NSWDPC/SpamProtection/FormBadge %>
<% end_if %>
