
<h1>Overview by Year</h1>
<table class="report-layout display">
    <thead>
    <tr>
        <th>Year</th>
        <th>Events</th>
        <th>Participants</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$eventsByYear item=row}
        <tr class="{cycle values="odd-row,even-row"}">
            <td>{$row[0]}</td>
            <td>{$row[1]}</td>
            <td>{$row[2]}</td>
        </tr>
    {/foreach}
    </tbody>
</table>
<p>&nbsp;</p>

<h1>Details of {$statsYear}</h1>
<h2>Summary by Event Type</h2>
<table class="report-layout display">
    <thead>
    <tr>
        <th>Event Type</th>
        <th>Total Events (per type)</th>
        <th>Total Attended participants (per type)</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$eventSummary item=row}
        <tr class="{cycle values="odd-row,even-row"}">
            <td>{$row.event_type}</td>
            <td>{$row.total_events}</td>
            <td>{$row.total_participants}</td>
        </tr>
    {/foreach}
    </tbody>
</table>
<p>&nbsp;</p>

<h2>All Events by Event Type</h2>
{foreach from=$events item=rows key=eventCategory}
    <h3>{$eventCategory}</h3>
    <table class="report-layout display">
        <thead>
        <tr>
            <th>Date</th>
            <th>Event</th>
            <th>Participants</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$rows item=row}
            <tr class="{cycle values="odd-row,even-row"}">
                <td>{$row.start_date}</td>
                <td>{$row.title}</td>
                <td>{$row.participants}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    <p>&nbsp;</p>

{/foreach}
<p>&nbsp;</p>

<h2>All Events</h2>
<p>See <a href="{$overviewURL}">list</a></p>
