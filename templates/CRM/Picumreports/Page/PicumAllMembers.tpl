
<table class="report-layout display">
    <thead>
    <tr>
        <th><a href="{$currentURL}&newsort=2">Country</a></th>
        <th><a href="{$currentURL}&newsort=3">Organization</a></th>
        <th><a href="{$currentURL}&newsort=4">Member Since</a></th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$members item=row}
        <tr class="{cycle values="odd-row,even-row"}">
            <td>{$row.country}</td>
            <td><a href="admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview&reset=1&cid={$row.id}">{$row.organization_name}</a></td>
            <td>{$row.start_date}</td>
        </tr>
    {/foreach}
    </tbody>
</table>