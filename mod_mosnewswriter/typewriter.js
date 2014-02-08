// Ticker main run loop
function co_runTheTicker(params) {
	if (!document.getElementById) 
		return;

	var theAnchorObject = document.getElementById(params.theAnchorName);
	if (!theAnchorObject)
		return;

	// Go for the next story data block
	if (params.theCurrentLength == 0) 	{
		params.theCurrentStory++;
		params.theCurrentStory = params.theCurrentStory % params.theItemCount;
	}

	// Stuff the current ticker text into the anchor
	var theStorySummary = params.theSummaries[params.theCurrentStory].replace(/&quot;/g,'\"');
	var theVisibleSummary = theStorySummary.substring(0,params.theCurrentLength);
	var thePrefix = '<span class=\'tickls\'>' + params.theLeadString + '</span>';
	theAnchorObject.href = params.theSiteLinks[params.theCurrentStory];
	theAnchorObject.innerHTML = thePrefix + theVisibleSummary + co_whatWidget(params, theStorySummary);

	// Modify the length for the substring and define the timer
	var nextTimeout;
	if (params.theCurrentLength != theStorySummary.length) {
		params.theCurrentLength++;
		nextTimeout = params.theCharacterTimeout;
	}
	else {
		params.theCurrentLength = 0;
		nextTimeout = params.theStoryTimeout;
	}

	// Call up the next cycle of the ticker
	setTimeout(function() { co_runTheTicker(params); }, nextTimeout);
}

// Widget generator
function co_whatWidget(params, theStorySummary) {
	if (params.theCurrentLength == theStorySummary.length) 	{
		return params.theWidgetNone;
	}
	if ((params.theCurrentLength % 2) == 1) {
		return params.theWidgetOne;
	}
	else {
		return params.theWidgetTwo;
	}
}

