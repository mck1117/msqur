<div class=tune-note-div>Tune Note: <?php
if ($isOwner && !isset($isReadOnly)) {
?>
<form id="tuneNoteForm" name="tuneNoteForm" action="#" method="post">
<input type="hidden" id="tune_id" name="tune_id" value="<?=$tune_id;?>">
<textarea id="tuneNote" name="tuneNote" cols="40" rows="1"><?=$tuneParams["tuneComment"];?></textarea>
&nbsp;&nbsp;<input type="button" class="tune-note-button" id="tuneSave" value="Save"/>&nbsp;&nbsp;<input type="button" class="tune-note-button" id="tuneCancel" value="Cancel" />
</form>
<script>
	$('#tuneNote').bind('input propertychange', function() {
		$('.tune-note-button').show();
	});

	$('#tuneSave').click(function() {
		$('#tuneNoteForm').submit();
	});
	$('#tuneCancel').click(function() {
		$('#tuneNoteForm')[0].reset();
		$('.tune-note-button').hide();
	});

	$("#tuneNoteForm").submit(function(e) {
		e.preventDefault();
		var form = $(this);
		var serData = form.serialize();
		form.find(':input:not(:disabled)').prop('disabled', true);
		$.ajax({
			type: "GET",
			url: "api.php?method=changeTuneNote",
			data: serData,
			success: function(response)
			{
				var isError = false;
				try {
					data = JSON.parse(response);
					if (data.changeTuneNote.status != "ok") {
						alert("Error! " + data.changeTuneNote.text);
						isError = true;
					}
				} catch (e) {
					alert("There was an error while processing. Cannot read the processed results!");
					isError = true;
				}

				$('.tune-note-button').hide();
				form.find(':input:disabled').prop('disabled',false);
				if (isError) {
					$('#tuneCancel').click();
				}
			},
			error: function(data)
			{
				alert("Error!");
				form.find(':input:disabled').prop('disabled',false);
				$('#tuneCancel').click();
			}
		});
	});
	
</script>
<?php
} else {
?>
<?=isset($tuneParams["tuneComment"]) ? $tuneParams["tuneComment"] : "";?>
<?php
}
?>
</div>