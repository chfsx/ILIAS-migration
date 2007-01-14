/**
 * ILIAS Open Source
 * --------------------------------
 * Implementation of ADL SCORM 2004
 * 
 * This program is free software. The use and distribution terms for this software
 * are covered by the GNU General Public License Version 2
 * 	<http://opensource.org/licenses/gpl-license.php>.
 * By using this software in any fashion, you are agreeing to be bound by the terms 
 * of this license.
 * 
 * You must not remove this notice, or any other, from this software.
 * 
 * PRELIMINARY EDITION
 * This is work in progress and therefore incomplete and buggy ...
 * 
 * Content-Type: application/x-javascript; charset=ISO-8859-1
 * Modul: this data will be needed for SCORM 1.2 compatibility later
 *  
 * @author Alfred Kohnert <alfred.kohnert@bigfoot.com>
 * @version $Id$
 * @copyright: (c) 2007 Alfred Kohnert
 */ 
 
 {
	'cmi.core._children' : 'No replacement',
	'cmi.core.student_id' : 'cmi.learner_id',
	'cmi.core.student_name' : 'cmi.learner_name',
	'cmi.core.lesson_location' : 'cmi.location',
	'cmi.core.credit' : 'cmi.credit',
	'cmi.core.lesson_status' : ['cmi.completion_status', 'cmi.success_status*'],
	'cmi.core.entry' : 'cmi.entry',
	'cmi.core.score._children' : 'cmi.score._children',
	'cmi.core.score.raw' : 'cmi.score.raw',
	'cmi.core.score.min' : 'cmi.score.min',
	'cmi.core.score.max' : 'cmi.score.max',
	'cmi.core.total_time' : 'cmi.total_time',
	'cmi.core.lesson_mode' : 'cmi.mode',
	'cmi.core.exit' : 'cmi.exit',
	'cmi.core.session_time' : 'cmi.session_time',
	'cmi.suspend_data' : 'cmi.suspend_data',
	'cmi.launch_data' : 'cmi.launch_data',
	'cmi.comments' : 'cmi.comments_from_learner.0.comment',
	'cmi.comments_from_lms' : 'cmi.comments_from_learner.0.comment',
	'cmi.student_preference._children' : 'cmi.learner_preference._children',
	'cmi.student_preference.audio' : 'cmi.learner_preference.audio_level',
	'cmi.student_preference.language' : 'cmi.learner_preference.language',
	'cmi.student_preference.speed' : 'cmi.learner_preference.delivery_speed',
	'cmi.student_preference.text' : 'cmi.learner_preference.audio_captioning',
	'cmi.student_data.mastery_score' : 'cmi.scaled_passing_score',
	'cmi.student_data.max_time_allowed' : 'cmi.max_time_allowed',
	'cmi.student_data.time_limit_action' : 'cmi.time_limit_action',
	'cmi.comments_from_lms' : 'cmi.comments_from_lms.0.comment',
	'cmi.comments' : 'cmi.comments_from_learner.0.comment'
}
{
	{0 : 'No error', 0 : 'No Error'},
	{101 : 'General Exception', 101 : 'General Exception'},
	{102 : 'General Initialization Failure'},
	{103 : 'Already Initialized'},
	{104 : 'Content Instance Terminated'},
	{111 : 'General Termination Failure'},
	{301 : 'Not initialized', 112 : 'Termination Before Initialization'},
	{113 : 'Termination After Termination'},
	{301 : 'Not initialized', 122 : 'Retrieve Data Before Initialization'},
	{123 : 'Retrieve Data After Termination'},
	{301 : 'Not initialized', 132 : 'Store Data Before Initialization'},
	{133 : 'Store Data After Termination'},
	{301 : 'Not initialized', 142 : 'Commit Before Initialization'},
	{143 : 'Commit After Termination'},
	{201 : 'Invalid argument error', 201 : 'General Argument Error'},
	{301 : 'General Get Failure'},
	{351 : 'General Set Failure'},
	{391 : 'General Commit Failure'},
	{201 : 'Invalid argument error, or'},
	{401 : 'Not implemented error', 401 : 'Undefined Data Model Element'},
	{401 : 'Not implemented error', 402 : 'Unimplemented Data Model Element'},
	{403 : 'Data Model Element Value Not Initialized'},
	{403 : 'Element is read only', 404 : 'Data Model Element Is Read Only'},
	{404 : 'Element is write only', 405 : 'Data Model Element Is Write Only'},
	{405 : 'Incorrect Data Type', 406 : 'Data Model Element Type Mismatch'},
	{405 : 'Incorrect Data Type', 407 : 'Data Model Element Value Out Of Range'},
	{408 : 'Data Model Dependency Not Established'},
	{202 : 'Element cannot have children', 301 : 'General GetValue Error'},
	{203 : 'Element not an array.  Cannot have count.', 301 : 'General GetValue Error'},
	{402 : 'Invalid set value, element is a keyword', 404 : 'Data Model Element Is Read Only'},
}



// run this mapping onTerminate and reverse mapping onInitialize
// check values against new validation
{
	'cmi.comments' : 'cmi.comments_from_learner.0.comment',
	'cmi.comments_from_lms' : 'cmi.comments_from_lms.0.comment',
	'cmi.core.credit' : 'cmi.credit',
	'cmi.core.entry' : 'cmi.entry',
	'cmi.core.exit' : 'cmi.exit',
	'cmi.core.lesson_location' : 'cmi.location',
	'cmi.core.lesson_mode' : 'cmi.mode',
	'cmi.core.lesson_status' : ['cmi.completion_status', 'cmi.success_status*'],
	'cmi.core.score._children' : 'cmi.score._children',
	'cmi.core.score.max' : 'cmi.score.max',
	'cmi.core.score.min' : 'cmi.score.min',
	'cmi.core.score.raw' : 'cmi.score.raw',
	'cmi.core.session_time' : 'cmi.session_time',
	'cmi.core.student_id' : 'cmi.learner_id',
	'cmi.core.student_name' : 'cmi.learner_name',
	'cmi.core.total_time' : 'cmi.total_time',
	'cmi.launch_data' : 'cmi.launch_data',
	'cmi.student_data.mastery_score' : 'cmi.scaled_passing_score',
	'cmi.student_data.max_time_allowed' : 'cmi.max_time_allowed',
	'cmi.student_data.time_limit_action' : 'cmi.time_limit_action',
	'cmi.student_preference._children' : 'cmi.learner_preference._children',
	'cmi.student_preference.audio' : 'cmi.learner_preference.audio_level',
	'cmi.student_preference.language' : 'cmi.learner_preference.language',
	'cmi.student_preference.speed' : 'cmi.learner_preference.delivery_speed',
	'cmi.student_preference.text' : 'cmi.learner_preference.audio_captioning',
	'cmi.suspend_data' : 'cmi.suspend_data',
}
