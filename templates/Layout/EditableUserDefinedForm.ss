<h1>$Title</h1>

<% if $ShowSubmittedList && $CompletedSubmissions %>
<div id="UserSubmissions">
	<h3>Submissions</h3>
	<ul class="formSubmissionList">
	<% loop CompletedSubmissions %>
		<li>
			<a href="$ViewLink">$Title</a>

			<a class="printPreviewLink" href="$ViewLink?print=1" target="_blank">Preview</a>
			<a class="pdfDownloadLink" href="$PDFLink" target="_blank">PDF</a>

			<% if Top.ShowDeleteButton %>
			<form class="inlineForm" method="POST" action="{$Top.Link}cancelsubmission" onsubmit="return confirm('Are you sure? All previously entered data will be lost!')">
				<input type="hidden" name="ResumeID" value="$ID" />
				<input type="submit" class="rowDeleteButton" name="action_cancelsubmission" value="Delete" />
			</form>
			<% end_if %>
		</li>
	<% end_loop %>
	</ul>
</div>
<% end_if %>

<% if $ShowDraftList && $DraftSubmissions %>
<div id="UserDrafts">
<h3>Your incomplete submissions</h3>
<ul class="formSubmissionList">
<% loop DraftSubmissions %>
	<li>
		<a href="$ResumeLink">$Title</a>

		<a class="printPreviewLink" href="$ViewLink?print=1" target="_blank">Preview</a>
		<a class="pdfDownloadLink" href="$PDFLink" target="_blank">PDF</a>

		<% if Top.ShowDeleteButton %>
		<form class="inlineForm" method="POST" action="{$Top.Link}cancelsubmission" onsubmit="return confirm('Are you sure? All previously entered data will be lost!')">
			<input type="hidden" name="ResumeID" value="$ID" />
			<input type="submit" class="rowDeleteButton" name="action_cancelsubmission" value="Delete" />
		</form>
		<% end_if %>
	</li>
<% end_loop %>
</ul>
</div>
<% end_if %>

<div class="formContent">
$Content
</div>

<div class="formForm">
$Form
</div>