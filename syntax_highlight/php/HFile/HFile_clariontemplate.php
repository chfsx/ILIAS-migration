<?php
global $BEAUT_PATH;
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_clariontemplate extends HFile{
   function HFile_clariontemplate(){
     $this->HFile();	
/*************************************/
// Beautifier Highlighting Configuration File 
// Clarion-Template
/*************************************/
// Flags

$this->nocase            	= "1";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "purple", "gray", "brown", "blue", "purple", "gray", "brown");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array("#IF", "#LOOP", "#FOR");
$this->unindent          	= array("#ENDIF", "#ENDLOOP", "#ENDFOR");

// String characters and delimiters

$this->stringchars       	= array("'");
$this->delimiters        	= array("~", "{", "}", "@", "^", "&", "*", "(", ")", "-", "+", "=", "|", "\\", "/", "[", "]", ";", "\"", "'", ">", ".", ",");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array("#!");
$this->blockcommenton    	= array("");
$this->blockcommentoff   	= array("");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"#ACCEPT" => "1", 
			"#APPLICATION" => "1", 
			"#AT" => "1", 
			"#ATEND" => "1", 
			"#ATSTART" => "1", 
			"#CODE" => "1", 
			"#CONTROL" => "1", 
			"#CONTEXT" => "1", 
			"#EMBED" => "1", 
			"#EMPTYEMBED" => "1", 
			"#ENDAT" => "1", 
			"#ENDCONTEXT" => "1", 
			"#ENDRESTRICT" => "1", 
			"#EXTENSION" => "1", 
			"#GROUP" => "1", 
			"#MODULE" => "1", 
			"#POSTEMBED" => "1", 
			"#PREEMBED" => "1", 
			"#PROCEDURE" => "1", 
			"#PROGRAM" => "1", 
			"#PRIORITY" => "1", 
			"#RESTRICT" => "1", 
			"#REJECT" => "1", 
			"#TEMPLATE" => "1", 
			"#UTILITY" => "1", 
			"#WHERE" => "1", 
			"#ADD" => "2", 
			"#ALIAS" => "2", 
			"#CLEAR" => "2", 
			"#DECLARE" => "2", 
			"#DELETE" => "2", 
			"#DELETEALL" => "2", 
			"#DEFAULT" => "2", 
			"#ENDDEFAULT" => "2", 
			"#ENDGLOBALDATA" => "2", 
			"#ENDLOCALDATA" => "2", 
			"#ENDREPORTS" => "2", 
			"#ENDWINDOWS" => "2", 
			"#FIND" => "2", 
			"#FIX" => "2", 
			"#FREE" => "2", 
			"#GLOBALDATA" => "2", 
			"#LOCALDATA" => "2", 
			"#LINK" => "2", 
			"#ORIG" => "2", 
			"#POP" => "2", 
			"#PURGE" => "2", 
			"#REPORTS" => "2", 
			"#REQ" => "2", 
			"#SELECT" => "2", 
			"#SET" => "2", 
			"#UNFIX" => "2", 
			"#WINDOWS" => "2", 
			"SAVE" => "2", 
			"UNIQUE" => "2", 
			"#BOXED" => "3", 
			"#BUTTON" => "3", 
			"#DISPLAY" => "3", 
			"#ENABLE" => "3", 
			"#ENDBOXED" => "3", 
			"#ENDBUTTON" => "3", 
			"#ENDCASE" => "4", 
			"#ENDENABLE" => "3", 
			"#ENDFIELD" => "3", 
			"#ENDPREPARE" => "3", 
			"#ENDSHEET" => "3", 
			"#ENDTAB" => "3", 
			"#ENDWITH" => "3", 
			"#FIELD" => "3", 
			"#IMAGE" => "3", 
			"#PREPARE" => "3", 
			"#PROMPT" => "3", 
			"#SHEET" => "3", 
			"#TAB" => "3", 
			"#VALIDATE" => "3", 
			"#WITH" => "3", 
			"#?" => "4", 
			"#ABORT" => "4", 
			"#APPEND" => "4", 
			"#BREAK" => "4", 
			"#CALL" => "4", 
			"#CASE" => "4", 
			"#CLOSE" => "4", 
			"#CYCLE" => "4", 
			"#CREATE" => "4", 
			"#ELSE" => "4", 
			"#ELSIF" => "4", 
			"#ENDFOR" => "4", 
			"#ENDIF" => "4", 
			"#ENDLOOP" => "4", 
			"#ENDSECTION" => "4", 
			"#FOR" => "4", 
			"#GENERATE" => "4", 
			"#IF" => "4", 
			"#INVOKE" => "4", 
			"#INDENT" => "4", 
			"#INSERT" => "4", 
			"#LOOP" => "4", 
			"#OF" => "4", 
			"#OROF" => "4", 
			"#OPEN" => "4", 
			"#PRINT" => "4", 
			"#READ" => "4", 
			"#REDIRECT" => "4", 
			"#RELEASE" => "4", 
			"#REMOVE" => "4", 
			"#REPLACE" => "4", 
			"#RESUME" => "4", 
			"#RETURN" => "4", 
			"#RUN" => "4", 
			"#RUNDLL" => "4", 
			"#SERVICE" => "4", 
			"#SUSPEND" => "4", 
			"#SECTION" => "4", 
			"#<" => "5", 
			"#ASSERT" => "5", 
			"#CLASS" => "5", 
			"#COMMENT" => "5", 
			"#DEBUG" => "5", 
			"#ERROR" => "5", 
			"#EXPORT" => "5", 
			"#HELP" => "5", 
			"#IMPORT" => "5", 
			"#INCLUDE" => "5", 
			"#MESSAGE" => "5", 
			"#PROJECT" => "5", 
			"#PROTOTYPE" => "5", 
			"%ActiveTemplate" => "6", 
			"%ActiveTemplateInstance" => "6", 
			"%ActiveTemplateOwnerInstance" => "6", 
			"%ActiveTemplateParentInstance" => "6", 
			"%ActiveTemplatePrimaryInstance" => "6", 
			"%AliasFile" => "6", 
			"%Application" => "6", 
			"%ApplicationDebug" => "6", 
			"%ApplicationLocalLibrary" => "6", 
			"%ApplicationTemplate" => "6", 
			"%ApplicationTemplateInstance" => "6", 
			"%ApplicationTemplateParentInstance" => "6", 
			"%BytesOutput" => "6", 
			"%ConditionalGenerate" => "6", 
			"%Control" => "6", 
			"%ControlAlert" => "6", 
			"%ControlDefaultHeight" => "6", 
			"%ControlDefaultWidth" => "6", 
			"%ControlEvent" => "6", 
			"%ControlField" => "6", 
			"%ControlFieldFormat" => "6", 
			"%ControlFieldHasColor" => "6", 
			"%ControlFieldHasIcon" => "6", 
			"%ControlFieldHasLocator" => "6", 
			"%ControlFieldHasTree" => "6", 
			"%ControlFieldHeading" => "6", 
			"%ControlFieldPicture" => "6", 
			"%ControlFrom" => "6", 
			"%ControlIndent" => "6", 
			"%ControlInstance" => "6", 
			"%ControlMenu" => "6", 
			"%ControlMenuBar" => "6", 
			"%ControlOriginal" => "6", 
			"%ControlParameter" => "6", 
			"%ControlParent" => "6", 
			"%ControlParentTab" => "6", 
			"%ControlParentType" => "6", 
			"%ControlStatement" => "6", 
			"%ControlTemplate" => "6", 
			"%ControlTool" => "6", 
			"%ControlToolBar" => "6", 
			"%ControlType" => "6", 
			"%ControlUnsplitStatement" => "6", 
			"%ControlUse" => "6", 
			"%CreateLocalMap" => "6", 
			"%DictionaryChanged" => "6", 
			"%DictionaryFile" => "6", 
			"%Driver" => "6", 
			"%DriverBinMemo" => "6", 
			"%DriverCreate" => "6", 
			"%DriverDescription" => "6", 
			"%DriverDLL" => "6", 
			"%DriverEncrypt" => "6", 
			"%DriverLIB" => "6", 
			"%DriverMaxKeys" => "6", 
			"%DriverMemo" => "6", 
			"%DriverOpcode" => "6", 
			"%DriverOwner" => "6", 
			"%DriverReclaim" => "6", 
			"%DriverRequired" => "6", 
			"%DriverSQL" => "6", 
			"%DriverType" => "6", 
			"%DriverUniqueKey" => "6", 
			"%EditFilename" => "6", 
			"%EditProcedure" => "6", 
			"%EmbedDescription" => "6", 
			"%EmbedID" => "6", 
			"%EmbedParameters" => "6", 
			"%EOF" => "6", 
			"%False" => "6", 
			"%Field" => "6", 
			"%FieldChoices" => "6", 
			"%FieldDescription" => "6", 
			"%FieldDimension1" => "6", 
			"%FieldDimension2" => "6", 
			"%FieldDimension3" => "6", 
			"%FieldDimension4" => "6", 
			"%FieldDisplayPicture" => "6", 
			"%FieldFile" => "6", 
			"%FieldFormatWidth" => "6", 
			"%FieldHeader" => "6", 
			"%FieldHelpID" => "6", 
			"%FieldID" => "6", 
			"%FieldIdent" => "6", 
			"%FieldInitial" => "6", 
			"%FieldJustIndent" => "6", 
			"%FieldJustType" => "6", 
			"%FieldLongDesc" => "6", 
			"%FieldLookup" => "6", 
			"%FieldMemoImage" => "6", 
			"%FieldMemoSize" => "6", 
			"%FieldName" => "6", 
			"%FieldPicture" => "6", 
			"%FieldPlaces" => "6", 
			"%FieldQuickOptions" => "6", 
			"%FieldRangeHigh" => "6", 
			"%FieldRangeLow" => "6", 
			"%FieldRecordPicture" => "6", 
			"%FieldReportControl" => "6", 
			"%FieldReportControlHeight" => "6", 
			"%FieldReportControlWidth" => "6", 
			"%FieldScreenControl" => "6", 
			"%FieldScreenControlHeight" => "6", 
			"%FieldScreenControlWidth" => "6", 
			"%FieldStatement" => "6", 
			"%FieldStruct" => "6", 
			"%FieldType" => "6", 
			"%FieldUserOptions" => "6", 
			"%FieldValidation" => "6", 
			"%File" => "6", 
			"%File32BitOnly" => "6", 
			"%FileBindable" => "6", 
			"%FileCreate" => "6", 
			"%FileDescription" => "6", 
			"%FileDriver" => "6", 
			"%FileDriverParameter" => "6", 
			"%FileEncrypt" => "6", 
			"%FileExternal" => "6", 
			"%FileExternalModule" => "6", 
			"%FileIdent" => "6", 
			"%FileKey" => "6", 
			"%FileKeyField" => "6", 
			"%FileKeyFieldLink" => "6", 
			"%FileLongDesc" => "6", 
			"%FileName" => "6", 
			"%FileOwner" => "6", 
			"%FilePrefix" => "6", 
			"%FilePrimaryKey" => "6", 
			"%FileQuickOptions" => "6", 
			"%FileReclaim" => "6", 
			"%FileRelationType" => "6", 
			"%FileStatement" => "6", 
			"%FileStruct" => "6", 
			"%FileStructEnd" => "6", 
			"%FileStructRec" => "6", 
			"%FileStructRecEnd" => "6", 
			"%FileThreaded" => "6", 
			"%FileType" => "6", 
			"%FileUserOptions" => "6", 
			"%FirstProcedure" => "6", 
			"%Formula" => "6", 
			"%FormulaClass" => "6", 
			"%FormulaDescription" => "6", 
			"%FormulaExpression" => "6", 
			"%FormulaExpressionCase" => "6", 
			"%FormulaExpressionFalse" => "6", 
			"%FormulaExpressionTrue" => "6", 
			"%FormulaExpressionType" => "6", 
			"%FormulaInstance" => "6", 
			"%GlobalData" => "6", 
			"%GlobalDataStatement" => "6", 
			"%HelpFile" => "6", 
			"%Key" => "6", 
			"%KeyAuto" => "6", 
			"%KeyDescription" => "6", 
			"%KeyDuplicate" => "6", 
			"%KeyExcludeNulls" => "6", 
			"%KeyField" => "6", 
			"%KeyFieldSequence" => "6", 
			"%KeyFile" => "6", 
			"%KeyID" => "6", 
			"%KeyIdent" => "6", 
			"%KeyIndex" => "6", 
			"%KeyLongDesc" => "6", 
			"%KeyName" => "6", 
			"%KeyNoCase" => "6", 
			"%KeyPrimary" => "6", 
			"%KeyQuickOptions" => "6", 
			"%KeyStatement" => "6", 
			"%KeyStruct" => "6", 
			"%KeyUserOptions" => "6", 
			"%LocalData" => "6", 
			"%LocalDataStatement" => "6", 
			"%MenuBarStatement" => "6", 
			"%Module" => "6", 
			"%ModuleBase" => "6", 
			"%ModuleChanged" => "6", 
			"%ModuleData" => "6", 
			"%ModuleDataStatement" => "6", 
			"%ModuleExtension" => "6", 
			"%ModuleExternal" => "6", 
			"%ModuleInclude" => "6", 
			"%ModuleLanguage" => "6", 
			"%ModuleProcedure" => "6", 
			"%ModuleReadOnly" => "6", 
			"%ModuleTemplate" => "6", 
			"%Null" => "6", 
			"%OtherFiles" => "6", 
			"%Primary" => "6", 
			"%PrimaryInstance" => "6", 
			"%PrimaryKey" => "6", 
			"%Procedure" => "6", 
			"%ProcedureCalled" => "6", 
			"%ProcedureDateChanged" => "6", 
			"%ProcedureDateCreated" => "6", 
			"%ProcedureDescription" => "6", 
			"%ProcedureExported" => "6", 
			"%ProcedureIsGlobal" => "6", 
			"%ProcedureLanguage" => "6", 
			"%ProcedureLongDescription" => "6", 
			"%ProcedureReadOnly" => "6", 
			"%ProcedureReturnType" => "6", 
			"%ProcedureTemplate" => "6", 
			"%ProcedureTimeChanged" => "6", 
			"%ProcedureTimeCreated" => "6", 
			"%ProcedureToDo" => "6", 
			"%ProcedureType" => "6", 
			"%Program" => "6", 
			"%ProgramDateChanged" => "6", 
			"%ProgramDateCreated" => "6", 
			"%ProgramExtension" => "6", 
			"%ProgramTimeChanged" => "6", 
			"%ProgramTimeCreated" => "6", 
			"%Prototype" => "6", 
			"%QuickProcedure" => "6", 
			"%RegistryChanged" => "6", 
			"%Relation" => "6", 
			"%RelationAlias" => "6", 
			"%RelationConstraintDelete" => "6", 
			"%RelationConstraintUpdate" => "6", 
			"%RelationKey" => "6", 
			"%RelationKeyField" => "6", 
			"%RelationKeyFieldLink" => "6", 
			"%RelationPrefix" => "6", 
			"%RelationQuickOptions" => "6", 
			"%RelationUserOptions" => "6", 
			"%Report" => "6", 
			"%ReportControl" => "6", 
			"%ReportControlField" => "6", 
			"%ReportControlIndent" => "6", 
			"%ReportControlInstance" => "6", 
			"%ReportControlLabel" => "6", 
			"%ReportControlOriginal" => "6", 
			"%ReportControlStatement" => "6", 
			"%ReportControlTemplate" => "6", 
			"%ReportControlType" => "6", 
			"%ReportControlUse" => "6", 
			"%ReportStatement" => "6", 
			"%Secondary" => "6", 
			"%SecondaryTo" => "6", 
			"%SecondaryType" => "6", 
			"%Target32" => "6", 
			"%ToolbarStatement" => "6", 
			"%True" => "6", 
			"%ViewFile" => "6", 
			"%ViewFileField" => "6", 
			"%ViewFileFields" => "6", 
			"%ViewFiles" => "6", 
			"%ViewFileStruct" => "6", 
			"%ViewFileStructEnd" => "6", 
			"%ViewFilter" => "6", 
			"%ViewJoinedTo" => "6", 
			"%ViewPrimary" => "6", 
			"%ViewPrimaryField" => "6", 
			"%ViewPrimaryFields" => "6", 
			"%ViewStatement" => "6", 
			"%ViewStruct" => "6", 
			"%ViewStructEnd" => "6", 
			"%Window" => "6", 
			"%WindowEvent" => "6", 
			"%WindowStatement" => "6", 
			"%pClassName" => "7", 
			"%pClassIncFile" => "7", 
			"%pClassMethod" => "7", 
			"%pClassMethodPrototype" => "7", 
			"%pClassMethodPrivate" => "7", 
			"%pClassMethodVirtual" => "7", 
			"%pClassMethodProtected" => "7", 
			"%pClassMethodProcAttribute" => "7", 
			"%pClassMethodInherited" => "7", 
			"%pClassMethodReturnType" => "7", 
			"%pClassMethodParentCall" => "7", 
			"%pClassProperty" => "7", 
			"%pClassPropertyPrototype" => "7", 
			"%pClassPropertyPrivate" => "7", 
			"%pClassPropertyProtected" => "7", 
			"%pClassPropertyInherited" => "7", 
			"%ClassMethodList" => "7", 
			"%SysActiveInvisible" => "7", 
			"%SysAllowUnfilled" => "7", 
			"%SysRetainRow" => "7", 
			"%SysResetOnGainFocus" => "7", 
			"%SysAutoToolbar" => "7", 
			"%SysAutoRefresh" => "7", 
			"%PropertyList" => "7", 
			"%MethodList" => "7", 
			"%ObjectList" => "7", 
			"%ObjectListType" => "7", 
			"%CWTemplateVersion" => "7", 
			"%IsExternal" => "7", 
			"%SaveCreateLocalMap" => "7", 
			"%GlobalIncludeList" => "7", 
			"%ModuleIncludeList" => "7", 
			"%CalloutModules" => "7", 
			"%ClassDeclarations" => "7", 
			"%OOPConstruct" => "7", 
			"%ByteCount" => "7", 
			"%IncludePrototype" => "7", 
			"%UsedFile" => "7", 
			"%ProcFilesUsed" => "7", 
			"%UsedDriverDLLs" => "7", 
			"%PrintPreviewUsed" => "7", 
			"%FileExternalFlag" => "7", 
			"%FileThreadedFlag" => "7", 
			"%IniFileName" => "7", 
			"%GenerationCompleted" => "7", 
			"%GenerateModule" => "7", 
			"%VBXList" => "7", 
			"%OLENeeded" => "7", 
			"%OCXList" => "7", 
			"%LastTarget32" => "7", 
			"%LastProgramExtension" => "7", 
			"%LastApplicationDebug" => "7", 
			"%LastApplicationLocalLibrary" => "7", 
			"%CustomGlobalMapModule" => "7", 
			"%CustomGlobalMapProcedure" => "7", 
			"%CustomGlobalMapProcedurePrototype" => "7", 
			"%CustomGlobalData" => "7", 
			"%CustomGlobalDataDeclaration" => "7", 
			"%CustomGlobalDataBeforeFiles" => "7", 
			"%CustomGlobalDataComponent" => "7", 
			"%CustomGlobalDataComponentIndent" => "7", 
			"%CustomGlobalDataComponentDeclaration" => "7", 
			"%CustomModuleMapModule" => "7", 
			"%CustomModuleMapProcedure" => "7", 
			"%CustomModuleMapProcedurePrototype" => "7", 
			"%CustomModuleData" => "7", 
			"%CustomModuleDataDeclaration" => "7", 
			"%CustomModuleDataComponent" => "7", 
			"%CustomModuleDataComponentIndent" => "7", 
			"%CustomModuleDataComponentDeclaration" => "7", 
			"%CustomGlobalMapIncludes" => "7", 
			"%CustomGlobalDeclarationIncludes" => "7", 
			"%CustomFlags" => "7", 
			"%CustomFlagSetting" => "7", 
			"%AccessMode" => "7", 
			"%BuildFile" => "7", 
			"%BuildHeader" => "7", 
			"%BuildInclude" => "7", 
			"%ExportFile" => "7", 
			"%ValueConstruct" => "7", 
			"%HoldConstruct" => "7", 
			"%RegenerateGlobalModule" => "7", 
			"%ClipName" => "7", 
			"%UpdateRelationPrimary" => "7", 
			"%UpdateRelationSecondary" => "7", 
			"%UpdateAttachedFile" => "7", 
			"%DeleteRelationPrimary" => "7", 
			"%DeleteRelationSecondary" => "7", 
			"%DeleteAttachedFile" => "7", 
			"%AllFile" => "7", 
			"%BRWList" => "7", 
			"%GlobalRegenerate" => "7", 
			"%FilesPerBCModule" => "7", 
			"%RelatesPerRoutine" => "7", 
			"**" => "8", 
			"BEEP:" => "8", 
			"BUTTON:" => "8", 
			"COLOR:" => "8", 
			"CREATE:" => "8", 
			"CURSOR:" => "8", 
			"DDE:" => "8", 
			"EVENT:" => "8", 
			"ff_:" => "8", 
			"FILE:" => "8", 
			"FONT:" => "8", 
			"ICON:" => "8", 
			"LISTZONE:" => "8", 
			"PEN:" => "8", 
			"PROP:" => "8", 
			"PROPLIST:" => "8", 
			"PROPPRINT:" => "8", 
			"REJECT:" => "8", 
			"STD:" => "8", 
			"VBXEVENT:" => "8", 
			"#FIELDS" => "8", 
			"FALSE" => "8", 
			"TRUE" => "8");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.
        
        

$this->linkscripts    	= array(
			"1" => "donothing", 
			"2" => "donothing", 
			"3" => "donothing", 
			"4" => "donothing", 
			"5" => "donothing", 
			"6" => "donothing", 
			"7" => "donothing", 
			"8" => "donothing");
}


function donothing($keywordin)
{
	return $keywordin;
}

}?>
