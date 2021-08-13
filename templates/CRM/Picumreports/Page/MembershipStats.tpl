<h2>Overview by year</h2>

<table class="report-layout display">
    <thead>
    <tr>
        <th>Year</th>
        <th>Total</th>
        <th>Countries</th>
        <th>New Members</th>
        <th>Withdrawals</th>
        <th>Terminated</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$membersByYear item=row}
        <tr class="{cycle values="odd-row,even-row"}">
            <td>{$row[0]}</td>
            <td>{$row[1]}</td>
            <td>{$row[2]}</td>
            <td>{$row[3]}</td>
            <td>{$row[4]}</td>
            <td>{$row[5]}</td>
        </tr>
    {/foreach}
    </tbody>
</table>
<p>&nbsp;</p>

<h2>Details of {$statsYear}</h2>

<h3>Members by Country</h3>
<table class="report-layout display">
    <thead>
        <tr>
            <th>Country</th>
            <th>Number of Members</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$membersCountbyCountry item=row}
            <tr class="{cycle values="odd-row,even-row"}">
                <td>{$row.country}</td>
                <td><a href="admin.php?page=CiviCRM&q=civicrm%2Fpicumallmembers&country_id={$row.country_id}">{$row.no_of_members}</a></td>
            </tr>
        {/foreach}
    </tbody>
</table>
<p>&nbsp;</p>

