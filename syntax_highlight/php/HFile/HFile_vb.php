<?php

global $BEAUT_PATH;
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_vb extends HFile{
   function HFile_vb(){
     $this->HFile();
     
/*************************************/
// Beautifier Highlighting Configuration File 
// Visual Basic
/*************************************/
// Flags

$this->nocase            	= "1";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "purple", "gray", "brown", "blue", "purple");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array("Private Sub", "Public Sub", "With");
$this->unindent          	= array("End Sub", "End With");

// String characters and delimiters

$this->stringchars       	= array();
$this->delimiters        	= array(" ", "(", ")", ".");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array("'");
$this->blockcommenton    	= array("");
$this->blockcommentoff   	= array("");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"Abs" => "1", 
			"Array" => "1", 
			"Asc" => "1", 
			"AscB" => "1", 
			"AscW" => "1", 
			"Atn" => "1", 
			"Avg" => "1", 
			"CBool" => "1", 
			"CByte" => "1", 
			"CCur" => "1", 
			"CDate" => "1", 
			"CDbl" => "1", 
			"Cdec" => "1", 
			"Choose" => "1", 
			"Chr" => "1", 
			"ChrB" => "1", 
			"ChrW" => "1", 
			"CInt" => "1", 
			"CLng" => "1", 
			"Command" => "1", 
			"Cos" => "1", 
			"Count" => "1", 
			"CreateObject" => "1", 
			"CSng" => "1", 
			"CStr" => "1", 
			"CurDir" => "1", 
			"CVar" => "1", 
			"CVDate" => "1", 
			"CVErr" => "1", 
			"Date" => "1", 
			"DateAdd" => "1", 
			"DateDiff" => "1", 
			"DatePart" => "1", 
			"DateSerial" => "1", 
			"DateValue" => "1", 
			"Day" => "1", 
			"DDB" => "1", 
			"Dir" => "1", 
			"DoEvents" => "1", 
			"Environ" => "1", 
			"EOF" => "1", 
			"Error" => "1", 
			"Exp" => "1", 
			"FileAttr" => "1", 
			"FileDateTime" => "1", 
			"FileLen" => "1", 
			"Fix" => "1", 
			"Format" => "1", 
			"FreeFile" => "1", 
			"FV" => "1", 
			"GetAllStrings" => "1", 
			"GetAttr" => "1", 
			"GetAutoServerSettings" => "1", 
			"GetObject" => "1", 
			"GetSetting" => "1", 
			"Hex" => "1", 
			"Hour" => "1", 
			"IIf" => "1", 
			"IMEStatus" => "1", 
			"Input" => "1", 
			"InputB" => "1", 
			"InputBox" => "1", 
			"InStr" => "1", 
			"InstB" => "1", 
			"Int" => "1", 
			"IPmt" => "1", 
			"IsArray" => "1", 
			"IsDate" => "1", 
			"IsEmpty" => "1", 
			"IsError" => "1", 
			"IsMissing" => "1", 
			"IsNull" => "1", 
			"IsNumeric" => "1", 
			"IsObject" => "1", 
			"LBound" => "1", 
			"LCase" => "1", 
			"Left" => "1", 
			"LeftB" => "1", 
			"Len" => "1", 
			"LenB" => "1", 
			"LoadPicture" => "1", 
			"Loc" => "1", 
			"LOF" => "1", 
			"Log" => "1", 
			"LTrim" => "1", 
			"Max" => "1", 
			"Mid" => "1", 
			"MidB" => "1", 
			"Min" => "1", 
			"Minute" => "1", 
			"MIRR" => "1", 
			"Month" => "1", 
			"MsgBox" => "1", 
			"Now" => "1", 
			"NPer" => "1", 
			"NPV" => "1", 
			"Oct" => "1", 
			"Partition" => "1", 
			"Pmt" => "1", 
			"PPmt" => "1", 
			"PV" => "1", 
			"QBColor" => "1", 
			"Rate" => "1", 
			"RGB" => "1", 
			"Right" => "1", 
			"RightB" => "1", 
			"Rnd" => "1", 
			"RTrim" => "1", 
			"Second" => "1", 
			"Seek" => "1", 
			"Sgn" => "1", 
			"Shell" => "1", 
			"Sin" => "1", 
			"SLN" => "1", 
			"Space" => "1", 
			"Spc" => "1", 
			"Sqr" => "1", 
			"StDev" => "1", 
			"StDevP" => "1", 
			"Str" => "1", 
			"StrComp" => "1", 
			"StrConv" => "1", 
			"String" => "1", 
			"Switch" => "1", 
			"Sum" => "1", 
			"SYD" => "1", 
			"Tab" => "1", 
			"Tan" => "1", 
			"Time" => "1", 
			"Timer" => "1", 
			"TimeSerial" => "1", 
			"TimeValue" => "1", 
			"Trim" => "1", 
			"TypeName" => "1", 
			"UBound" => "1", 
			"UCase" => "1", 
			"Val" => "1", 
			"Var" => "1", 
			"VarP" => "1", 
			"VarType" => "1", 
			"Weekday" => "1", 
			"Year" => "1", 
			"Accept" => "2", 
			"Activate" => "2", 
			"Add" => "2", 
			"AddCustom" => "2", 
			"AddFile" => "2", 
			"AddFromFile" => "2", 
			"AddFromTemplate" => "2", 
			"AddItem" => "2", 
			"AddNew" => "2", 
			"AddToAddInToolbar" => "2", 
			"AddToolboxProgID" => "2", 
			"Append" => "2", 
			"AppendChunk" => "2", 
			"Arrange" => "2", 
			"Assert" => "2", 
			"AsyncRead" => "2", 
			"BatchUpdate" => "2", 
			"BeginTrans" => "2", 
			"Bind" => "2", 
			"Cancel" => "2", 
			"CancelAsyncRead" => "2", 
			"CancelBatch" => "2", 
			"CancelUpdate" => "2", 
			"CanPropertyChange" => "2", 
			"CaptureImage" => "2", 
			"CellText" => "2", 
			"CellValue" => "2", 
			"Circle" => "2", 
			"Clear" => "2", 
			"ClearFields" => "2", 
			"ClearSel" => "2", 
			"ClearSelCols" => "2", 
			"Clone" => "2", 
			"Close" => "2", 
			"Cls" => "2", 
			"ColContaining" => "2", 
			"ColumnSize" => "2", 
			"CommitTrans" => "2", 
			"CompactDatabase" => "2", 
			"Compose" => "2", 
			"Connect" => "2", 
			"Copy" => "2", 
			"CopyQueryDef" => "2", 
			"CreateDatabase" => "2", 
			"CreateDragImage" => "2", 
			"CreateEmbed" => "2", 
			"CreateField" => "2", 
			"CreateGroup" => "2", 
			"CreateIndex" => "2", 
			"CreateLink" => "2", 
			"CreatePreparedStatement" => "2", 
			"CreatePropery" => "2", 
			"CreateQuery" => "2", 
			"CreateQueryDef" => "2", 
			"CreateRelation" => "2", 
			"CreateTableDef" => "2", 
			"CreateUser" => "2", 
			"CreateWorkspace" => "2", 
			"Customize" => "2", 
			"Delete" => "2", 
			"DeleteColumnLabels" => "2", 
			"DeleteColumns" => "2", 
			"DeleteRowLabels" => "2", 
			"DeleteRows" => "2", 
			"DoVerb" => "2", 
			"Drag" => "2", 
			"Draw" => "2", 
			"Edit" => "2", 
			"EditCopy" => "2", 
			"EditPaste" => "2", 
			"EndDoc" => "2", 
			"EnsureVisible" => "2", 
			"EstablishConnection" => "2", 
			"Execute" => "2", 
			"ExtractIcon" => "2", 
			"Fetch" => "2", 
			"FetchVerbs" => "2", 
			"Files" => "2", 
			"FillCache" => "2", 
			"Find" => "2", 
			"FindFirst" => "2", 
			"FindItem" => "2", 
			"FindLast" => "2", 
			"FindNext" => "2", 
			"FindPrevious" => "2", 
			"Forward" => "2", 
			"GetBookmark" => "2", 
			"GetChunk" => "2", 
			"GetClipString" => "2", 
			"GetData" => "2", 
			"GetFirstVisible" => "2", 
			"GetFormat" => "2", 
			"GetHeader" => "2", 
			"GetLineFromChar" => "2", 
			"GetNumTicks" => "2", 
			"GetRows" => "2", 
			"GetSelectedPart" => "2", 
			"GetText" => "2", 
			"GetVisibleCount" => "2", 
			"GoBack" => "2", 
			"GoForward" => "2", 
			"Hide" => "2", 
			"HitTest" => "2", 
			"HoldFields" => "2", 
			"Idle" => "2", 
			"InitializeLabels" => "2", 
			"InsertColumnLabels" => "2", 
			"InsertColumns" => "2", 
			"InsertObjDlg" => "2", 
			"InsertRowLabels" => "2", 
			"InsertRows" => "2", 
			"Item" => "2", 
			"KillDoc" => "2", 
			"Layout" => "2", 
			"Line" => "2", 
			"LinkExecute" => "2", 
			"LinkPoke" => "2", 
			"LinkRequest" => "2", 
			"LinkSend" => "2", 
			"Listen" => "2", 
			"LoadFile" => "2", 
			"LoadResData" => "2", 
			"LoadResPicture" => "2", 
			"LoadResString" => "2", 
			"LogEvent" => "2", 
			"MakeCompileFile" => "2", 
			"MakeReplica" => "2", 
			"MoreResults" => "2", 
			"Move" => "2", 
			"MoveData" => "2", 
			"MoveFirst" => "2", 
			"MoveLast" => "2", 
			"MoveNext" => "2", 
			"MovePrevious" => "2", 
			"NavigateTo" => "2", 
			"NewPage" => "2", 
			"NewPassword" => "2", 
			"NextRecordset" => "2", 
			"OLEDrag" => "2", 
			"OnAddinsUpdate" => "2", 
			"OnConnection" => "2", 
			"OnDisconnection" => "2", 
			"OnStartupComplete" => "2", 
			"Open" => "2", 
			"OpenConnection" => "2", 
			"OpenDatabase" => "2", 
			"OpenQueryDef" => "2", 
			"OpenRecordset" => "2", 
			"OpenResultset" => "2", 
			"OpenURL" => "2", 
			"Overlay" => "2", 
			"PaintPicture" => "2", 
			"Paste" => "2", 
			"PastSpecialDlg" => "2", 
			"PeekData" => "2", 
			"Play" => "2", 
			"Point" => "2", 
			"PopulatePartial" => "2", 
			"PopupMenu" => "2", 
			"Print" => "2", 
			"PrintForm" => "2", 
			"PropertyChanged" => "2", 
			"PSet" => "2", 
			"Quit" => "2", 
			"Raise" => "2", 
			"RandomDataFill" => "2", 
			"RandomFillColumns" => "2", 
			"RandomFillRows" => "2", 
			"rdoCreateEnvironment" => "2", 
			"rdoRegisterDataSource" => "2", 
			"ReadFromFile" => "2", 
			"ReadProperty" => "2", 
			"Rebind" => "2", 
			"ReFill" => "2", 
			"Refresh" => "2", 
			"RefreshLink" => "2", 
			"RegisterDatabase" => "2", 
			"Reload" => "2", 
			"Remove" => "2", 
			"RemoveAddInFromToolbar" => "2", 
			"RemoveItem" => "2", 
			"Render" => "2", 
			"RepairDatabase" => "2", 
			"Reply" => "2", 
			"ReplyAll" => "2", 
			"Requery" => "2", 
			"ResetCustom" => "2", 
			"ResetCustomLabel" => "2", 
			"ResolveName" => "2", 
			"RestoreToolbar" => "2", 
			"Resync" => "2", 
			"Rollback" => "2", 
			"RollbackTrans" => "2", 
			"RowBookmark" => "2", 
			"RowContaining" => "2", 
			"RowTop" => "2", 
			"Save" => "2", 
			"SaveAs" => "2", 
			"SaveFile" => "2", 
			"SaveToFile" => "2", 
			"SaveToolbar" => "2", 
			"SaveToOle1File" => "2", 
			"Scale" => "2", 
			"ScaleX" => "2", 
			"ScaleY" => "2", 
			"Scroll" => "2", 
			"Select" => "2", 
			"SelectAll" => "2", 
			"SelectPart" => "2", 
			"SelPrint" => "2", 
			"Send" => "2", 
			"SendData" => "2", 
			"Set" => "2", 
			"SetAutoServerSettings" => "2", 
			"SetData" => "2", 
			"SetFocus" => "2", 
			"SetOption" => "2", 
			"SetSize" => "2", 
			"SetText" => "2", 
			"SetViewport" => "2", 
			"Show" => "2", 
			"ShowColor" => "2", 
			"ShowFont" => "2", 
			"ShowHelp" => "2", 
			"ShowOpen" => "2", 
			"ShowPrinter" => "2", 
			"ShowSave" => "2", 
			"ShowWhatsThis" => "2", 
			"SignOff" => "2", 
			"SignOn" => "2", 
			"Size" => "2", 
			"Span" => "2", 
			"SplitContaining" => "2", 
			"StartLabelEdit" => "2", 
			"StartLogging" => "2", 
			"Stop" => "2", 
			"Synchronize" => "2", 
			"TextHeight" => "2", 
			"TextWidth" => "2", 
			"ToDefaults" => "2", 
			"TwipsToChartPart" => "2", 
			"TypeByChartType" => "2", 
			"Update" => "2", 
			"UpdateControls" => "2", 
			"UpdateRecord" => "2", 
			"UpdateRow" => "2", 
			"Upto" => "2", 
			"WhatsThisMode" => "2", 
			"WriteProperty" => "2", 
			"ZOrder" => "2", 
			"AccessKeyPress" => "3", 
			"AfterAddFile" => "3", 
			"AfterChangeFileName" => "3", 
			"AfterCloseFile" => "3", 
			"AfterColEdit" => "3", 
			"AfterColUpdate" => "3", 
			"AfterDelete" => "3", 
			"AfterInsert" => "3", 
			"AfterLabelEdit" => "3", 
			"AfterRemoveFile" => "3", 
			"AfterUpdate" => "3", 
			"AfterWriteFile" => "3", 
			"AmbienChanged" => "3", 
			"ApplyChanges" => "3", 
			"Associate" => "3", 
			"AsyncReadComplete" => "3", 
			"AxisActivated" => "3", 
			"AxisLabelActivated" => "3", 
			"AxisLabelSelected" => "3", 
			"AxisLabelUpdated" => "3", 
			"AxisSelected" => "3", 
			"AxisTitleActivated" => "3", 
			"AxisTitleSelected" => "3", 
			"AxisTitleUpdated" => "3", 
			"AxisUpdated" => "3", 
			"BeforeClick" => "3", 
			"BeforeColEdit" => "3", 
			"BeforeColUpdate" => "3", 
			"BeforeConnect" => "3", 
			"BeforeDelete" => "3", 
			"BeforeInsert" => "3", 
			"BeforeLabelEdit" => "3", 
			"BeforeLoadFile" => "3", 
			"BeforeUpdate" => "3", 
			"ButtonClick" => "3", 
			"ButtonCompleted" => "3", 
			"ButtonGotFocus" => "3", 
			"ButtonLostFocus" => "3", 
			"Change" => "3", 
			"ChartActivated" => "3", 
			"ChartSelected" => "3", 
			"ChartUpdated" => "3", 
			"Click" => "3", 
			"ColEdit" => "3", 
			"Collapse" => "3", 
			"ColResize" => "3", 
			"ColumnClick" => "3", 
			"Compare" => "3", 
			"ConfigChageCancelled" => "3", 
			"ConfigChanged" => "3", 
			"ConnectionRequest" => "3", 
			"DataArrival" => "3", 
			"DataChanged" => "3", 
			"DataUpdated" => "3", 
			"DblClick" => "3", 
			"Deactivate" => "3", 
			"DeviceArrival" => "3", 
			"DeviceOtherEvent" => "3", 
			"DeviceQueryRemove" => "3", 
			"DeviceQueryRemoveFailed" => "3", 
			"DeviceRemoveComplete" => "3", 
			"DeviceRemovePending" => "3", 
			"DevModeChange" => "3", 
			"Disconnect" => "3", 
			"DisplayChanged" => "3", 
			"Dissociate" => "3", 
			"DoGetNewFileName" => "3", 
			"Done" => "3", 
			"DonePainting" => "3", 
			"DownClick" => "3", 
			"DragDrop" => "3", 
			"DragOver" => "3", 
			"DropDown" => "3", 
			"EditProperty" => "3", 
			"EnterCell" => "3", 
			"EnterFocus" => "3", 
			"Event" => "4", 
			"ExitFocus" => "3", 
			"Expand" => "3", 
			"FootnoteActivated" => "3", 
			"FootnoteSelected" => "3", 
			"FootnoteUpdated" => "3", 
			"GotFocus" => "3", 
			"HeadClick" => "3", 
			"InfoMessage" => "3", 
			"Initialize" => "3", 
			"IniProperties" => "3", 
			"ItemActivated" => "3", 
			"ItemAdded" => "3", 
			"ItemCheck" => "3", 
			"ItemClick" => "3", 
			"ItemReloaded" => "3", 
			"ItemRemoved" => "3", 
			"ItemRenamed" => "3", 
			"ItemSeletected" => "3", 
			"KeyDown" => "3", 
			"KeyPress" => "3", 
			"KeyUp" => "3", 
			"LeaveCell" => "3", 
			"LegendActivated" => "3", 
			"LegendSelected" => "3", 
			"LegendUpdated" => "3", 
			"LinkClose" => "3", 
			"LinkError" => "3", 
			"LinkNotify" => "3", 
			"LinkOpen" => "3", 
			"Load" => "3", 
			"LostFocus" => "3", 
			"MouseDown" => "3", 
			"MouseMove" => "3", 
			"MouseUp" => "3", 
			"NodeClick" => "3", 
			"ObjectMove" => "3", 
			"OLECompleteDrag" => "3", 
			"OLEDragDrop" => "3", 
			"OLEDragOver" => "3", 
			"OLEGiveFeedback" => "3", 
			"OLESetData" => "3", 
			"OLEStartDrag" => "3", 
			"OnAddNew" => "3", 
			"OnComm" => "3", 
			"Paint" => "3", 
			"PanelClick" => "3", 
			"PanelDblClick" => "3", 
			"PathChange" => "3", 
			"PatternChange" => "3", 
			"PlotActivated" => "3", 
			"PlotSelected" => "3", 
			"PlotUpdated" => "3", 
			"PointActivated" => "3", 
			"PointLabelActivated" => "3", 
			"PointLabelSelected" => "3", 
			"PointLabelUpdated" => "3", 
			"PointSelected" => "3", 
			"PointUpdated" => "3", 
			"PowerQuerySuspend" => "3", 
			"PowerResume" => "3", 
			"PowerStatusChanged" => "3", 
			"PowerSuspend" => "3", 
			"QueryChangeConfig" => "3", 
			"QueryComplete" => "3", 
			"QueryCompleted" => "3", 
			"QueryTimeout" => "3", 
			"QueryUnload" => "3", 
			"ReadProperties" => "3", 
			"Reposition" => "3", 
			"RequestChangeFileName" => "3", 
			"RequestWriteFile" => "3", 
			"Resize" => "3", 
			"ResultsChanged" => "3", 
			"RowColChange" => "3", 
			"RowCurrencyChange" => "3", 
			"RowResize" => "3", 
			"RowStatusChanged" => "3", 
			"SelChange" => "3", 
			"SelectionChanged" => "3", 
			"SendComplete" => "3", 
			"SendProgress" => "3", 
			"SeriesActivated" => "3", 
			"SeriesSelected" => "3", 
			"SeriesUpdated" => "3", 
			"SettingChanged" => "3", 
			"SplitChange" => "3", 
			"StateChanged" => "3", 
			"StatusUpdate" => "3", 
			"SysColorsChanged" => "3", 
			"Terminate" => "3", 
			"TimeChanged" => "3", 
			"TitleActivated" => "3", 
			"TitleSelected" => "3", 
			"UnboundAddData" => "3", 
			"UnboundDeleteRow" => "3", 
			"UnboundGetRelativeBookmark" => "3", 
			"UnboundReadData" => "3", 
			"UnboundWriteData" => "3", 
			"Unload" => "3", 
			"UpClick" => "3", 
			"Updated" => "3", 
			"Validate" => "3", 
			"ValidationError" => "3", 
			"WillAssociate" => "3", 
			"WillChangeData" => "3", 
			"WillDissociate" => "3", 
			"WillExecute" => "3", 
			"WillUpdateRows" => "3", 
			"WithEvents" => "3", 
			"WriteProperties" => "3", 
			"AppActivate" => "4", 
			"Base" => "4", 
			"Beep" => "4", 
			"Call" => "4", 
			"Case" => "4", 
			"ChDir" => "4", 
			"ChDrive" => "4", 
			"Const" => "4", 
			"Declare" => "4", 
			"DefBool" => "4", 
			"DefByte" => "4", 
			"DefCur" => "4", 
			"DefDate" => "4", 
			"DefDbl" => "4", 
			"DefDec" => "4", 
			"DefInt" => "4", 
			"DefLng" => "4", 
			"DefObj" => "4", 
			"DefSng" => "4", 
			"DefStr" => "4", 
			"Deftype" => "4", 
			"DefVar" => "4", 
			"DeleteSetting" => "4", 
			"Dim" => "4", 
			"Do" => "4", 
			"Else" => "4", 
			"ElseIf" => "4", 
			"End" => "4", 
			"Enum" => "4", 
			"Erase" => "4", 
			"Exit" => "4", 
			"Explicit" => "4", 
			"False" => "4", 
			"FileCopy" => "4", 
			"For" => "4", 
			"ForEach" => "4", 
			"Friend" => "4", 
			"Function" => "4", 
			"Get" => "4", 
			"GoSub" => "4", 
			"GoTo" => "4", 
			"Height" => "4",
			"If" => "4", 
			"Implements" => "4", 
			"Kill" => "4", 
			"Left" => "4",
			"Let" => "4", 
			"LineInput" => "4", 
			"Lock" => "4", 
			"Loop" => "4", 
			"LSet" => "4", 
			"MkDir" => "4", 
			"Name" => "4", 
			"Next" => "4", 
			"Not" => "4", 
			"OnError" => "4", 
			"On" => "4", 
			"Option" => "4", 
			"Private" => "4", 
			"Property" => "4", 
			"Public" => "4", 
			"Put" => "4", 
			"RaiseEvent" => "4", 
			"Randomize" => "4", 
			"ReDim" => "4", 
			"Rem" => "4", 
			"Reset" => "4", 
			"Resume" => "4", 
			"Return" => "4", 
			"RmDir" => "4", 
			"RSet" => "4", 
			"SavePicture" => "4", 
			"SaveSetting" => "4", 
			"SendKeys" => "4", 
			"SetAttr" => "4", 
			"Static" => "4", 
			"Sub" => "4", 
			"Then" => "4", 
			"Top" => "4",
			"True" => "4", 
			"Type" => "4", 
			"Unlock" => "4", 
			"Wend" => "4", 
			"While" => "4", 
			"Width" => "4", 
			"With" => "4", 
			"Write" => "4", 
			"&" => "5");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.



$this->linkscripts    	= array(
			"1" => "donothing", 
			"2" => "donothing", 
			"3" => "donothing", 
			"4" => "donothing", 
			"5" => "donothing");

}



function donothing($keywordin)
{
	return $keywordin;
}

}

?>
