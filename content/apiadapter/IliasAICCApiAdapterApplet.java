/*
 * SCORM-1.2 API-Adapter Java Applet for ILIAS
 * Copyright (c) Matthai Kurian, Jan Gellweiler, Alexander Killing
 *
 * Made for ILIAS, the same license terms as for ILIAS itself apply.
 *
 * This Applet handles communication between ILIAS and SCORM-1.2
 * Sharable Content Objects (SCOs). Most communication is via Liveconnect.
 * CMI (Computer Managed Instruction) data is sent to ILIAS through http POST
 * to an ILIAS server side PHP script. SCORM-1.2 runtime behavior and CMI
 * datamodel management is done by the PfPLMS SCORM-1.2 API-Adapter Core. 
 */

import java.util.Hashtable;
import java.util.Enumeration;
import java.net.*;
import java.io.*;

public	class IliasAICCApiAdapterApplet
	extends java.applet.Applet
	implements ch.ethz.pfplms.scorm.api.ApiAdapterInterface
{
	private	ch.ethz.pfplms.scorm.api.ApiAdapter core;

	private Hashtable IliasAICCCmi  = new Hashtable();

	private String  IliasStudentId;
	private String  IliasStudentName;
	private String  IliasRefId;
	private String  IliasAICCId;

	private boolean IliasCredit = false;
	private boolean isLaunched  = false;
	private boolean isVerbose   = false;

	public IliasAICCApiAdapterApplet () {
		core = new ch.ethz.pfplms.scorm.api.ApiAdapter ();
	}

	public	final void init () {
		//if (getParameter("verbose") != null)
		isVerbose = true;
		IliasRefId       = getParameter ("ref_id");
		IliasStudentId   = getParameter ("student_id");
		IliasStudentName = getParameter ("student_name");
		if (getParameter ("credit") != null) {
			IliasCredit = true;
			say ("cmi.core.credit=credit");
		}
		say ("cmi.core.student_id=" +IliasStudentId);
		say ("cmi.core.student_name=" +IliasStudentName);
	}

	private final void say (String s) {
		if (isVerbose) System.out.println (s);
	}

	private final void IliasLaunchContent (String s) {
		try {
			getAppletContext().showDocument (
				new URL(getCodeBase()+s),
				"aicc_content"
			);
		} catch (Exception e) {}
	}

	public	final void IliasLaunchAICC (String aicc_id) {
		if (aicc_id == null) return;
		if (isLaunched && aicc_id.equals(IliasAICCId)) {
			say ("AICC " +aicc_id +" is already running.");
			return;
		}
		IliasAICCCmi.clear();
		say ("Launching AICC " +aicc_id);
		if (isLaunched) say ("AICC "+IliasAICCId +" will be unloaded.");
		isLaunched = false;
		IliasLaunchContent (
			"../aicc_presentation.php?cmd=launchAICC"
			+"&aicc_id=" + aicc_id
			+"&ref_id=" + IliasRefId
		);
		IliasAICCId = aicc_id;
	}

	public	final void IliasSetValue (String l, String r) {
		say ("IliasSetValue("+l+"="+r+")");
		if (l != null && r != null) IliasAICCCmi.put (l, r);
	}

	private	final void IliasInitialize () {
		core.sysPut ("cmi.core.student_id",   IliasStudentId);
		core.sysPut ("cmi.core.student_name", IliasStudentName);
		if (IliasCredit) {
			core.sysPut ("cmi.core.credit", "credit");
			core.sysPut ("cmi.core.lesson_mode", "normal");
		}
		core.sysPut (IliasAICCCmi);
		core.transBegin();
		isLaunched = true;
	}

	private final String IliasCommit () {
		core.transEnd();
		StringBuffer P = new StringBuffer();
		Hashtable ins = core.getTransNew ();
		Hashtable mod = core.getTransMod ();
		core.transBegin();

		int i=0;
		for (Enumeration e = ins.keys(); e.hasMoreElements(); i++) {
			Object l = e.nextElement();
			Object r = ins.get(l);
			P.append("&iL["+i+"]="+l.toString());
			P.append("&iR["+i+"]="+URLEncoder.encode(r.toString()));
		}

		int u=0;
		for (Enumeration e = mod.keys(); e.hasMoreElements(); u++) {
			Object l = e.nextElement();
			Object r = mod.get(l);
			P.append("&uL["+u+"]="+l.toString());
			P.append("&uR["+u+"]="+URLEncoder.encode(r.toString()));
		}

		if (i == 0 && u == 0) {
			say ("Nothing to do.");
			return "true";
		}

		HttpURLConnection http;

		try {
			http = (HttpURLConnection) ( new java.net.URL (
				getCodeBase().toString()
				+ "../aicc_server.php"
				+ "?cmd=store" 
				+ "&api=2" 
				+ "&user_id="+IliasStudentId
				+ "&aicc_id=" +IliasAICCId
			)).openConnection();

			http.setRequestProperty (
				"Content-Type",
				"application/x-www-form-urlencoded"
			);
			http.setRequestProperty (
				"Content-Length",
				Integer.toString (P.length())
			);
			http.setDoOutput (true);
			http.setUseCaches (false);
			http.setRequestMethod ("POST");
			http.setAllowUserInteraction (false);

			OutputStream os = http.getOutputStream();
			say ("post:" +P.toString());
			os.write (P.toString().getBytes());
			os.close ();
			DataInputStream r = new DataInputStream(
				http.getInputStream ()
			);

			try {
				say (r.readUTF());
				r.close ();
			} catch (EOFException ok) {}

			say (i +" inserted.");
			say (u +" updated.");
			return "true";

		} catch (Exception e) {
			say ("Ilias cmi storage failed.");
			say (e.toString());
			return "false";
		}
	}

	private	final void IliasFinish () {
		core.transEnd();
		if (!isLaunched) return;
		IliasLaunchContent (
			"../aicc_presentation.php?cmd=view"
			+"&ref_id=" + IliasRefId
		);
		isLaunched = false;
	}

	/*
	 * Liveconnect interface methods for SCO
	 */

	public	final String LMSInitialize (String s) { 
		core.reset();
		String rv = core.LMSInitialize(s);
		IliasInitialize ();
		say ("LMSInitialize("+s+")="+rv);
		return rv;
	}

	public	final String LMSCommit (String s) {
		String rv = core.LMSCommit(s);
		if (rv.equals("false")) return rv;
		rv = IliasCommit(); 
		say ("LMSCommit("+s+")="+rv);
		return rv;
	}

	public	final String LMSFinish (String s) {
		String rv = core.LMSFinish(s);
		say ("LMSFinish("+s+")="+rv);
		if (rv.equals("false")) return rv;
		IliasFinish();
		return rv;
	}

	public	final String LMSGetDiagnostic (String e) {
		String rv = core.LMSGetDiagnostic (e);
		say ("LMSGetDiagnostic("+e+")="+rv);
		return rv;
	}

	public	final String LMSGetErrorString (String e) {
		String rv = core.LMSGetErrorString (e);
		say ("LMSGetErrorString("+e+")="+rv);
		return rv;
	}

	public	final String LMSGetLastError () {
		String rv = core.LMSGetLastError ();
		say ("LMSLastError()="+rv);
		return rv;
	}

	public	final String LMSGetValue (String l) {
		String rv = core.LMSGetValue (l);
		say ("LMSGetValue("+l+")="+rv);
		return rv;
	}

	public	final String LMSSetValue (String l, String r) {
		String rv = core.LMSSetValue (l, r);
		say ("LMSSetValue("+l+"="+r+")="+rv);
		return rv;
	}
}
