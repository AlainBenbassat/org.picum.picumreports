<h3>Current Members</h3>
<p>PICUM currently has {$noOfCurrentMembers} members in {$noOfCurrentCountries} different countries.</p>

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

<h3>New Members by Year</h3>
<table class="report-layout display">
    <thead>
    <tr>
        <th>Year</th>
        <th>Number of New Members</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$newMembersByYear item=row}
        <tr class="{cycle values="odd-row,even-row"}">
            <td>{$row.start_year}</td>
            <td>{$row.no_of_members}</td>
        </tr>
    {/foreach}
    </tbody>
</table>
<p>&nbsp;</p>