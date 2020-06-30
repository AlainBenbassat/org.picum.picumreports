<p>&lt; {$returnURL}</p>

{foreach from=$events item=rows key=eventCategory}
    <h2>{$eventCategory}</h2>
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