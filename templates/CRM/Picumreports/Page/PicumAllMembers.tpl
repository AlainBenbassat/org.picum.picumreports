<p>
    Membership status: Current members | New Members | Withdrawals | Terminated
    <br>aparate pagina maken: FORMER MEMBERS
</p>
<table class="report-layout display">
    <thead>
    <tr>
        <th><a href="{$currentURL}&newsort=2">Country</a></th>
        <th><a href="{$currentURL}&newsort=3">Organization</a></th>
        <th><a href="{$currentURL}&newsort=4">Member Since</a></th>
        <th><a href="{$currentURL}&newsort=5">End Date</a></th>
        <th><a href="{$currentURL}&newsort=6">Last seen at event</a></th>
        <th><a href="{$currentURL}&newsort=7">N° of events attended this year</a></th>
        <th><a href="{$currentURL}&newsort=8">N° of events attended last year</a></th>
        <th><a href="{$currentURL}&newsort=9">Communication Channels</a></th>
        <th><a href="{$currentURL}&newsort=10">Code of Conduct Signed</a></th>
        <th><a href="{$currentURL}&newsort=11">Last Membership Fee</a></th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$members item=row}
        <tr class="{cycle values="odd-row,even-row"}">
            <td>{$row.country}</td>
            <td><a href="admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview&reset=1&cid={$row.id}">{$row.organization_name}</a></td>
            <td>{$row.start_date}</td>
            <td>{$row.end_date}</td>
            <td>{$row.last_seen_on}</td>
            <td>{$row.no_of_events_this_year}</td>
            <td>{$row.no_of_events_last_year}</td>
            <td>{$row.comm_channels}</td>
            <td>{$row.code_of_conduct}</td>
            <td>{$row.contribution_status}</td>
        </tr>
    {/foreach}
    </tbody>
</table>