<h2>Overview</h2>
<h3>Current Members</h3>

<p>PICUM currently has {$noOfCurrentMembers} members in {$noOfCurrentCountries} different countries.</p>

<h2>Details</h2>

<h3>Current Members by Country</h3>
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
                <td>{$row.no_of_members}</td>
            </tr>
        {/foreach}
    </tbody>
</table>
<p>&nbsp;</p>

<h3>Memberships by Year</h3>
<table class="report-layout display">
    <thead>
    <tr>
        <th>Year</th>
        <th>Total</th>
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
        </tr>
    {/foreach}
    </tbody>
</table>
<p>&nbsp;</p>

