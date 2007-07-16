// JS port of ADL SeqObjective.java
function SeqObjective()  
{
	this.mMaps = null;
}
this.SeqObjective = SeqObjective;
//new SeqObjective();
SeqObjective.prototype = 
{
	mObjId: "_primary_",
	mSatisfiedByMeasure: false,
	mActiveMeasure: true,
	mMinMeasure: 1.0,
	mContributesToRollup: false
}
SeqObjective.prototype.ff = "nj";
