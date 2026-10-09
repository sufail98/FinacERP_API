--
-- PostgreSQL database dump
--

\restrict gau8v1AJfLTv3WPYfV8p4RTx0PCh6IndaCqc8BYDwMxIL54vWvM2lwwuBit4A8B

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

-- Started on 2025-11-04 16:54:11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 410 (class 1255 OID 37277)
-- Name: create_area(bigint, character varying, text, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.create_area(areaid bigint, areaname character varying, narration text, createduser character varying, branchid integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_areaId BIGINT;
    v_exists INT;
BEGIN
    -- Check duplicate within the same branch (case-insensitive)
    SELECT COUNT(*) INTO v_exists
    FROM "tbl_Area"
    WHERE LOWER("AreaName") = LOWER(AreaName)
      AND "branchId" = branchId;

    IF v_exists > 0 THEN
        RAISE EXCEPTION 'Area Name Already Exists for this Branch!';
    END IF;

    -- Insert new area
    INSERT INTO "tbl_Area"(
        "AreaId","AreaName", "Narration", "branchId", "CreatedDate", "CreatedUser", "ModifiedDate", "ModifiedUser"
    )
    VALUES (
        AreaId,
        AreaName,
        Narration,
        branchId,
        NOW(),
        CreatedUser,
        NULL,
        NULL
    )
    RETURNING "AreaId" INTO v_areaId;

    RETURN v_areaId;
END;
$$;


ALTER FUNCTION public.create_area(areaid bigint, areaname character varying, narration text, createduser character varying, branchid integer) OWNER TO postgres;

--
-- TOC entry 387 (class 1255 OID 27291)
-- Name: delete_area(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_area(areaid bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Check if area exists
    IF NOT EXISTS (SELECT 1 FROM "tbl_Area" WHERE "AreaId" = AreaId) THEN
        RAISE EXCEPTION 'Area not found';
    END IF;

    -- Delete area
    DELETE FROM "tbl_Area"
    WHERE "AreaId" = AreaId;
END;
$$;


ALTER FUNCTION public.delete_area(areaid bigint) OWNER TO postgres;

--
-- TOC entry 395 (class 1255 OID 34574)
-- Name: delete_bom(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_bom(productcode character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_BOM"
    WHERE "productCode" = productCode;
END;
$$;


ALTER FUNCTION public.delete_bom(productcode character varying) OWNER TO postgres;

--
-- TOC entry 361 (class 1255 OID 62221)
-- Name: delete_delivery_note_details1_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_delivery_note_details1_byid(p_deliverynotemasterid bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_DeliveryNoteDetails1"
    WHERE "deliveryNoteMasterId" = p_deliveryNoteMasterId;

    RAISE NOTICE 'Delivery Note Deleted . p_deliveryNoteMasterId: %', p_deliveryNoteMasterId;
END;
$$;


ALTER FUNCTION public.delete_delivery_note_details1_byid(p_deliverynotemasterid bigint) OWNER TO postgres;

--
-- TOC entry 308 (class 1255 OID 62223)
-- Name: delete_delivery_note_master_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_delivery_note_master_byid(p_deliverynotemasterid bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_DeliveryNoteMaster"
    WHERE "deliveryNoteMasterId" = p_deliveryNoteMasterId;

    RAISE NOTICE 'Delivery Note Master Deleted . p_deliveryNoteMasterId: %', p_deliveryNoteMasterId;
END;
$$;


ALTER FUNCTION public.delete_delivery_note_master_byid(p_deliverynotemasterid bigint) OWNER TO postgres;

--
-- TOC entry 385 (class 1255 OID 27456)
-- Name: delete_ledger_posting(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_ledger_posting(vouchertype character varying, voucherno integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_LedgerPosting"
    WHERE "voucherType" = voucherType
      AND "voucherNo" = voucherNo;
END;
$$;


ALTER FUNCTION public.delete_ledger_posting(vouchertype character varying, voucherno integer) OWNER TO postgres;

--
-- TOC entry 381 (class 1255 OID 27457)
-- Name: delete_partybalance(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_partybalance(vouchertype character varying, voucherno integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_Partybalance"
    WHERE "voucherType" = voucherType
      AND "voucherNo" = voucherNo;
END;
$$;


ALTER FUNCTION public.delete_partybalance(vouchertype character varying, voucherno integer) OWNER TO postgres;

--
-- TOC entry 341 (class 1255 OID 34571)
-- Name: delete_product_salesprice(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_product_salesprice(productcode character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_ProductSalesPrice"
    WHERE "productCode" = productCode;
END;
$$;


ALTER FUNCTION public.delete_product_salesprice(productcode character varying) OWNER TO postgres;

--
-- TOC entry 326 (class 1255 OID 37702)
-- Name: delete_proforma_invoice_details1_byid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_proforma_invoice_details1_byid(proformamasterid integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_proformaInvoiceDetails1"
    WHERE "proformaMasterId" = proformaMasterId;
END;
$$;


ALTER FUNCTION public.delete_proforma_invoice_details1_byid(proformamasterid integer) OWNER TO postgres;

--
-- TOC entry 412 (class 1255 OID 37706)
-- Name: delete_proforma_invoice_master_byid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_proforma_invoice_master_byid(proformamasterid integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_proformaInvoiceMaster"
    WHERE "proformaMasterId" = proformaMasterId;
END;
$$;


ALTER FUNCTION public.delete_proforma_invoice_master_byid(proformamasterid integer) OWNER TO postgres;

--
-- TOC entry 377 (class 1255 OID 37334)
-- Name: delete_quotation_details(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_quotation_details(quotationmasterid integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_QuotationDetails"
    WHERE "quotationMasterId" = quotationMasterId;
END;
$$;


ALTER FUNCTION public.delete_quotation_details(quotationmasterid integer) OWNER TO postgres;

--
-- TOC entry 380 (class 1255 OID 37350)
-- Name: delete_quotation_master_byid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_quotation_master_byid(quotationmasterid integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_QuotationMaster"
    WHERE "quotationMasterId" = quotationMasterId;
END;
$$;


ALTER FUNCTION public.delete_quotation_master_byid(quotationmasterid integer) OWNER TO postgres;

--
-- TOC entry 368 (class 1255 OID 27499)
-- Name: delete_receipt_details(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_receipt_details(receiptmasterid bigint) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_count INT;
BEGIN
    -- Count records before deleting (optional)
    SELECT COUNT(*) INTO v_count
    FROM "tbl_Receiptdetails"
    WHERE "receiptMasterId" = receiptMasterId;

    -- Perform deletion
    DELETE FROM "tbl_Receiptdetails"
    WHERE "receiptMasterId" = receiptMasterId;

    -- Return message
    IF v_count > 0 THEN
        RETURN FORMAT('Deleted %s record(s) for receiptMasterId = %s', v_count, receiptMasterId);
    ELSE
        RETURN FORMAT('No records found for receiptMasterId = %s', receiptMasterId);
    END IF;
END;
$$;


ALTER FUNCTION public.delete_receipt_details(receiptmasterid bigint) OWNER TO postgres;

--
-- TOC entry 335 (class 1255 OID 27451)
-- Name: delete_sales_details1(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_sales_details1(salesmasterid bigint) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_count INT;
BEGIN
    -- Count records before deleting (optional)
    SELECT COUNT(*) INTO v_count
    FROM "tbl_SalesDetails1"
    WHERE "salesMasterId" = salesMasterId;

    -- Perform deletion
    DELETE FROM "tbl_SalesDetails1"
    WHERE "salesMasterId" = salesMasterId;

    -- Return message
    IF v_count > 0 THEN
        RETURN FORMAT('Deleted %s record(s) for salesMasterId = %s', v_count, salesMasterId);
    ELSE
        RETURN FORMAT('No records found for salesMasterId = %s', salesMasterId);
    END IF;
END;
$$;


ALTER FUNCTION public.delete_sales_details1(salesmasterid bigint) OWNER TO postgres;

--
-- TOC entry 357 (class 1255 OID 27478)
-- Name: delete_sales_details_by_sales_master_id(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_sales_details_by_sales_master_id(salesmasterid bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_SalesDetails1"
    WHERE "salesMasterId" = salesMasterId;

    RAISE NOTICE 'Deleted sales details for salesMasterId: %', salesMasterId;
END;
$$;


ALTER FUNCTION public.delete_sales_details_by_sales_master_id(salesmasterid bigint) OWNER TO postgres;

--
-- TOC entry 325 (class 1255 OID 27479)
-- Name: delete_sales_master_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_sales_master_byid(salesmasterid bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_SalesMaster"
    WHERE "salesMasterId" = salesMasterId;

    RAISE NOTICE 'Deleted sales details for salesMasterId: %', salesMasterId;
END;
$$;


ALTER FUNCTION public.delete_sales_master_byid(salesmasterid bigint) OWNER TO postgres;

--
-- TOC entry 358 (class 1255 OID 54061)
-- Name: delete_sales_order_details1_byid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_sales_order_details1_byid(ordermasterid integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_SalesOrderdetails1"
    WHERE "orderMasterId" = orderMasterId;

    RAISE NOTICE 'Sales Order Details Deleted . orderMasterId: %', orderMasterId;
END;
$$;


ALTER FUNCTION public.delete_sales_order_details1_byid(ordermasterid integer) OWNER TO postgres;

--
-- TOC entry 403 (class 1255 OID 54063)
-- Name: delete_sales_order_master_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_sales_order_master_byid(ordermasterid bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_SalesOrderMaster"
    WHERE "orderMasterId" = orderMasterId;

    RAISE NOTICE 'Sales Master Deleted . orderMasterId: %', orderMasterId;
END;
$$;


ALTER FUNCTION public.delete_sales_order_master_byid(ordermasterid bigint) OWNER TO postgres;

--
-- TOC entry 388 (class 1255 OID 27591)
-- Name: delete_sales_return_details(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_sales_return_details(returnmasterid bigint) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_count INT;
BEGIN
    -- Count records before deleting (optional)
    SELECT COUNT(*) INTO v_count
    FROM "tbl_SalesReturnDetails1"
    WHERE "ReturnMasterId" = ReturnMasterId;

    -- Perform deletion
    DELETE FROM "tbl_SalesReturnDetails1"
    WHERE "ReturnMasterId" = ReturnMasterId;

    -- Return message
    IF v_count > 0 THEN
        RETURN FORMAT('Deleted %s record(s) for ReturnMasterId = %s', v_count, ReturnMasterId);
    ELSE
        RETURN FORMAT('No records found for ReturnMasterId = %s', ReturnMasterId);
    END IF;
END;
$$;


ALTER FUNCTION public.delete_sales_return_details(returnmasterid bigint) OWNER TO postgres;

--
-- TOC entry 383 (class 1255 OID 27593)
-- Name: delete_sales_return_master_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_sales_return_master_byid(returnmasterid bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_SalesReturnMaster"
    WHERE "ReturnMasterId" = ReturnMasterId;

    RAISE NOTICE 'Deleted sales details for ReturnMasterId: %', ReturnMasterId;
END;
$$;


ALTER FUNCTION public.delete_sales_return_master_byid(returnmasterid bigint) OWNER TO postgres;

--
-- TOC entry 390 (class 1255 OID 27454)
-- Name: delete_stock_posting(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_stock_posting(vouchertype character varying, voucherno character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_count INT;
BEGIN
    -- Count matching records (optional but useful for debugging/logs)
    SELECT COUNT(*) INTO v_count
    FROM "tbl_StockPosting"
    WHERE "voucherType" = voucherType
      AND "voucherNo" = voucherNo;

    -- Perform deletion
    DELETE FROM "tbl_StockPosting"
    WHERE "voucherType" = voucherType
      AND "voucherNo" = voucherNo;

    -- Return message
    IF v_count > 0 THEN
        RETURN FORMAT('Deleted %s record(s) for voucherType = %s, voucherNo = %s', v_count, voucherType, voucherNo);
    ELSE
        RETURN FORMAT('No records found for voucherType = %s, voucherNo = %s', voucherType, voucherNo);
    END IF;
END;
$$;


ALTER FUNCTION public.delete_stock_posting(vouchertype character varying, voucherno character varying) OWNER TO postgres;

--
-- TOC entry 406 (class 1255 OID 34573)
-- Name: delete_unitconversion(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_unitconversion(productcode character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM public."tbl_UnitConversion"
    WHERE "productCode" = productCode;
END;
$$;


ALTER FUNCTION public.delete_unitconversion(productcode character varying) OWNER TO postgres;

--
-- TOC entry 348 (class 1255 OID 45922)
-- Name: get_all_account_ledgers(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_all_account_ledgers() RETURNS TABLE("ledgerId" bigint, "ledgerName" character varying, "groupId" integer, "billBybill" boolean, "openingBalance" numeric, "crOrDr" character varying, narration text, "nameArb" character varying, "accountNo" character varying, address text, "phoneNo" character varying, "faxNo" character varying, email character varying, "creditPeriod" integer, "creditLimit" numeric, "pricingLevelId" integer, "currencyId" integer, "interestOrNot" boolean, "branchId" integer, "marketId" integer, "default" boolean, "tinNumber" character varying, "cstNumber" character varying, "panNumber" character varying, "extraDate" timestamp without time zone, extra1 character varying, extra2 character varying, "areaId" integer, "ledgerCode" character varying, "BuildingNo" character varying, "AdditionalNo" character varying, "StreetName" character varying, "PostboxNo" character varying, "CityName" character varying, "Country" character varying, "creditLimitStatus" character varying, bankaccname character varying, bankname character varying, ibanno character varying, "District" character varying, "StreetNameArb" character varying, "BuildingNoArb" character varying, "CityNameArb" character varying, "DistrictArb" character varying, "CountryArb" character varying, "AdditionalNoArb" character varying, "PostboxNoArb" character varying, "bankBranchName" character varying, "bankSwiftCode" character varying, "AddressArabic" character varying, "ledgerType" character varying, "routeId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a."ledgerId",
        a."ledgerName",
        a."groupId",
        a."billBybill",
        a."openingBalance",
        a."crOrDr",
        a.narration,
        a."nameArb",
        a."accountNo",
        a.address,
        a."phoneNo",
        a."faxNo",
        a.email,
        a."creditPeriod",
        a."creditLimit",
        a."pricingLevelId",
        a."currencyId",
        a."interestOrNot",
        a."branchId",
        a."marketId",
        a."default",
        a."tinNumber",
        a."cstNumber",
        a."panNumber",
        a."extraDate",
        a.extra1,
        a.extra2,
        a."areaId",
        a."ledgerCode",
        a."BuildingNo",
        a."AdditionalNo",
        a."StreetName",
        a."PostboxNo",
        a."CityName",
        a."Country",
        a."creditLimitStatus",
        a.bankaccname,
        a.bankname,
        a.ibanno,
        a."District",
        a."StreetNameArb",
        a."BuildingNoArb",
        a."CityNameArb",
        a."DistrictArb",
        a."CountryArb",
        a."AdditionalNoArb",
        a."PostboxNoArb",
        a."bankBranchName",
        a."bankSwiftCode",
        a."AddressArabic",
        a."ledgerType",
        a."routeId",
        a."CreatedDate",
        a."CreatedUser",
        a."ModifiedDate",
        a."ModifiedUser"
    FROM public."tbl_AccountLedger" a
    ORDER BY a."ledgerId";
END;
$$;


ALTER FUNCTION public.get_all_account_ledgers() OWNER TO postgres;

--
-- TOC entry 367 (class 1255 OID 62158)
-- Name: get_all_delivery_note_master_list(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_all_delivery_note_master_list() RETURNS TABLE("deliveryNoteMasterId" bigint, "suffixPrefixId" integer, "voucherNo" character varying, "deliveryNoteNo" character varying, date timestamp without time zone, "ledgerId" integer, "customerName" character varying, "CustomerAddress" character varying, "CustomerPhone" character varying, "CustomerVATNo" character varying, "RefNo" character varying, "RefDate" timestamp without time zone, "costCentreId" integer, "pricingLevelId" integer, "employeeId" integer, narration text, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "LPONo" character varying, "LPODate" timestamp without time zone, "AgainstNo" character varying, "orderMasterId" integer, "quotationMasterId" integer, "proformaMasterId" integer, "BatchId" character varying, "taxType" character varying, "totalAmount" numeric, "taxableAmt" numeric, "additionalCost" numeric, "totalTax" numeric, "billDiscount" numeric, "roundOff" numeric, othercharge numeric, "otherChargeLedgerId" integer, "taxTotal" numeric, "grandTotal" numeric, "transportCompany" character varying, "deliveryLocation" character varying, "driverName" character varying, "contactNo" character varying, "deliveryVehicleNo" character varying, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "LedgerName" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        m."deliveryNoteMasterId",
        m."suffixPrefixId",
        m."voucherNo",
        m."deliveryNoteNo",
        m."date",
        m."ledgerId",
        m."customerName",
        m."CustomerAddress",
        m."CustomerPhone",
        m."CustomerVATNo",
        m."RefNo",
        m."RefDate",
        m."costCentreId",
        m."pricingLevelId",
        m."employeeId",
        m."narration",
        m."currencyConversionId",
        m."exchangeRate",
        m."exchangeDate",
        m."LPONo",
        m."LPODate",
        m."AgainstNo",
        m."orderMasterId",
        m."quotationMasterId",
        m."proformaMasterId",
        m."BatchId",
        m."taxType",
        m."totalAmount",
        m."taxableAmt",
        m."additionalCost",
        m."totalTax",
        m."billDiscount",
        m."roundOff",
        m."othercharge",
        m."otherChargeLedgerId",
        m."taxTotal",
        m."grandTotal",
        m."transportCompany",
        m."deliveryLocation",
        m."driverName",
        m."contactNo",
        m."deliveryVehicleNo",
        m."postedStatus",
        m."postedBy",
        m."postedDate",
        m."branchId",
        m."CreatedDate",
        m."CreatedUser",
        l."ledgerName" AS "LedgerName"

    FROM public."tbl_DeliveryNoteMaster" m
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = m."ledgerId"
    ORDER BY m."deliveryNoteMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_all_delivery_note_master_list() OWNER TO postgres;

--
-- TOC entry 389 (class 1255 OID 54053)
-- Name: get_all_sales_order_master_list(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_all_sales_order_master_list() RETURNS TABLE("orderMasterId" bigint, "suffixPrefixId" integer, "voucherNo" character varying, "orderNo" character varying, date timestamp without time zone, "dueDate" timestamp without time zone, "ledgerId" integer, "ledgerName" character varying, "partyName" character varying, "partyAddress" character varying, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "pricingLevelId" integer, "employeeId" integer, narration text, "costCentreId" integer, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "AgainstNo" character varying, "quotationMasterId" integer, "proformaMasterId" integer, "LPONO" character varying, "LPODate" timestamp without time zone, status character varying, "taxType" character varying, "subTotal" numeric, "additionalCost" numeric, "billDiscount" numeric, othercharge numeric, "taxableAmt" numeric, "totalTax" numeric, "roundOff" numeric, "totalAmount" numeric, "otherChargeLedgerId" character varying, "lrNo" character varying, "transportCompany" character varying, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        M."orderMasterId",
        M."suffixPrefixId",
        M."voucherNo",
        M."orderNo",
        M.date,
        M."dueDate",
        M."ledgerId",
        L."ledgerName",
        M."partyName",
        M."partyAddress",
        M."partyMobile",
        M."partyVatNo",
        M."partyRefNo",
        M."partyRefDate",
        M."pricingLevelId",
        M."employeeId",
        M."narration",
        M."costCentreId",
        M."currencyConversionId",
        M."exchangeRate",
        M."exchangeDate",
        M."AgainstNo",
        M."quotationMasterId",
        M."proformaMasterId",
        M."LPONO",
        M."LPODate",
        M."status",
        M."taxType",
        M."subTotal",
        M."additionalCost",
        M."billDiscount",
        M."othercharge",
        M."taxableAmt",
        M."totalTax",
        M."roundOff",
        M."totalAmount",
        M."otherChargeLedgerId",
        M."lrNo",
        M."transportCompany",
        M."branchId",
        M."CreatedDate",
        M."CreatedUser",
        M."ModifiedDate",
        M."ModifiedUser"
    FROM public."tbl_SalesOrderMaster" AS M
    LEFT JOIN public."tbl_AccountLedger" AS L
        ON M."ledgerId" = L."ledgerId"
    ORDER BY M."CreatedDate" DESC;
END;
$$;


ALTER FUNCTION public.get_all_sales_order_master_list() OWNER TO postgres;

--
-- TOC entry 350 (class 1255 OID 27284)
-- Name: get_area_by_id(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_area_by_id(p_id bigint) RETURNS TABLE("AreaId" bigint, "AreaName" character varying, "Narration" text, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a."AreaId",
        a."AreaName",
        a."Narration",
        a."CreatedDate",
        a."CreatedUser",
        a."ModifiedDate",
        a."ModifiedUser"
    FROM "tbl_Area" a
    WHERE a."AreaId" = p_id;
END;
$$;


ALTER FUNCTION public.get_area_by_id(p_id bigint) OWNER TO postgres;

--
-- TOC entry 374 (class 1255 OID 27283)
-- Name: get_areas(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_areas() RETURNS TABLE("AreaId" bigint, "AreaName" character varying, "Narration" text, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a."AreaId",
        a."AreaName",
        a."Narration",
        a."CreatedDate",
        a."CreatedUser",
        a."ModifiedDate",
        a."ModifiedUser"
    FROM "tbl_Area" a
    ORDER BY a."AreaName" ASC;
END;
$$;


ALTER FUNCTION public.get_areas() OWNER TO postgres;

--
-- TOC entry 339 (class 1255 OID 45913)
-- Name: get_currency_with_conversion(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_currency_with_conversion() RETURNS TABLE(currencyid bigint, currencysymbol character varying, currencyname character varying, subunitname character varying, noofdecimalplace integer, narration text, defaultflag boolean, branchid_currency character varying, createddate_currency timestamp without time zone, createduser_currency character varying, modifieddate_currency timestamp without time zone, modifieduser_currency character varying, currencyconversionid bigint, currencyid_conversion integer, date timestamp without time zone, rate numeric, narration_conversion text, branchid_conversion integer, createddate_conversion timestamp without time zone, createduser_conversion character varying, modifieddate_conversion timestamp without time zone, modifieduser_conversion character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        c."currencyId",
        c."currencySymbol",
        c."currencyName",
        c."subunitName",
        c."noOfDecimalPlace",
        c.narration,
        c."default" AS defaultFlag,
        c."branchId" AS branchId_currency,
        c."CreatedDate" AS CreatedDate_currency,
        c."CreatedUser" AS CreatedUser_currency,
        c."ModifiedDate" AS ModifiedDate_currency,
        c."ModifiedUser" AS ModifiedUser_currency,
        v."currencyConversionId",
        v."currencyId" AS currencyId_conversion,
        v.date,
        v.rate,
        v.narration AS narration_conversion,
        v."branchId" AS branchId_conversion,
        v."CreatedDate" AS CreatedDate_conversion,
        v."CreatedUser" AS CreatedUser_conversion,
        v."ModifiedDate" AS ModifiedDate_conversion,
        v."ModifiedUser" AS ModifiedUser_conversion
    FROM public."tbl_Currency" c
    JOIN public."tbl_CurrencyConversion" v 
        ON v."currencyId" = c."currencyId"
    ORDER BY c."currencyId" ASC;
END;
$$;


ALTER FUNCTION public.get_currency_with_conversion() OWNER TO postgres;

--
-- TOC entry 336 (class 1255 OID 27376)
-- Name: get_current_ledger_balance(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_current_ledger_balance(ledgerid integer, branchid integer, currencyid integer) RETURNS TABLE(currentbal numeric, balance numeric, crordr boolean)
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
    v_balance NUMERIC(20,2) := 0;
    v_crdr BOOLEAN := NULL;
    v_debit NUMERIC(20,2) := 0;
    v_credit NUMERIC(20,2) := 0;
    v_nodec INT := 2;
BEGIN
    -- Aggregate debit & credit
    SELECT COALESCE(SUM("debit"),0), COALESCE(SUM("credit"),0)
    INTO v_debit, v_credit
    FROM "tbl_LedgerPosting"
    WHERE "ledgerId" = ledgerId
      AND "branchId" = branchId;

    -- Determine balance and Cr/Dr flag
    IF v_debit < v_credit THEN
        v_balance := v_credit - v_debit;
        v_crdr := TRUE;  -- Credit
    ELSIF v_debit > v_credit THEN
        v_balance := v_debit - v_credit;
        v_crdr := FALSE; -- Debit
    ELSE
        v_balance := 0;
        v_crdr := NULL; -- Equal, no balance
    END IF;

    -- Get decimal precision from currency
    SELECT "noOfDecimalPlace"
    INTO v_nodec
    FROM "tbl_Currency"
    WHERE "currencyId" =  currencyId;

    -- Return results
    RETURN QUERY
    SELECT 
        COALESCE(ROUND(v_balance, v_nodec), 0)::NUMERIC(24,2) AS currentBal,
        0::NUMERIC(24,2) AS Balance,
        v_crdr AS CrOrDr;

END;
$$;


ALTER FUNCTION public.get_current_ledger_balance(ledgerid integer, branchid integer, currencyid integer) OWNER TO postgres;

--
-- TOC entry 332 (class 1255 OID 62170)
-- Name: get_delivery_note_details_by_masterid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_details_by_masterid(p_deliverynotemasterid bigint) RETURNS TABLE("deliveryNoteDetails1Id" bigint, "deliveryNoteMasterId" integer, "productCode" character varying, "SlNo" integer, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "ConversionFactor" numeric, barcode text, "discountPercentage" numeric, "taxAmount" numeric, "grossAmount" numeric, "taxType" character varying, "netAmount" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "PurchaseRate" numeric, otherchargeonproduct numeric, "Remark" text, "taxId" integer, "GodownId" integer, "RackId" character varying, "orderDetails1Id" integer, "quotationDetailsId" integer, "proformaDetails1Id" integer, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, "productName" character varying, "UnitName" character varying, "taxName" character varying, "GodownName" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        dnd."deliveryNoteDetails1Id",
        dnd."deliveryNoteMasterId",
        dnd."productCode",
        dnd."SlNo",
        dnd.qty,
        dnd."freeQty",
        dnd.rate,
        dnd."unitId",
        dnd.amount,
        dnd."ConversionFactor",
        dnd.barcode,
        dnd."discountPercentage",
        dnd."taxAmount",
        dnd."grossAmount",
        dnd."taxType",
        dnd."netAmount",
        dnd."billDiscOnProduct",
        dnd."AddCostonProduct",
        dnd."productDescription",
        dnd."PurchaseRate",
        dnd."otherchargeonproduct",
        dnd."Remark",
        dnd."taxId",
        dnd."GodownId",
        dnd."RackId",
        dnd."orderDetails1Id",
        dnd."quotationDetailsId",
        dnd."proformaDetails1Id",
        dnd."branchId",
        dnd."CreatedDate",
        dnd."CreatedUser",
        dnd."ModifiedDate",
        dnd."ModifiedUser",

        -- Joined fields
        p."productName",
        u."UnitName",
        t."taxName",
        g."GodownName"

    FROM public."tbl_DeliveryNoteDetails1" dnd
    LEFT JOIN public."tbl_Product" p ON p."productCode" = dnd."productCode"
    LEFT JOIN public."tbl_Unit" u ON u."unitId" = dnd."unitId"
    LEFT JOIN public."tbl_TaxMaster" t ON t."taxId" = dnd."taxId"
    LEFT JOIN public."tbl_Godown" g ON g."GodownId" = dnd."GodownId"
    WHERE dnd."deliveryNoteMasterId" = p_deliverynotemasterid
    ORDER BY dnd."SlNo";
END;
$$;


ALTER FUNCTION public.get_delivery_note_details_by_masterid(p_deliverynotemasterid bigint) OWNER TO postgres;

--
-- TOC entry 314 (class 1255 OID 62164)
-- Name: get_delivery_note_master_data_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_master_data_byid(p_deliverynotemasterid bigint) RETURNS TABLE("deliveryNoteMasterId" bigint, "suffixPrefixId" integer, "voucherNo" character varying, "deliveryNoteNo" character varying, date timestamp without time zone, "ledgerId" integer, "customerName" character varying, "CustomerAddress" character varying, "CustomerPhone" character varying, "CustomerVATNo" character varying, "RefNo" character varying, "RefDate" timestamp without time zone, "costCentreId" integer, "pricingLevelId" integer, "employeeId" integer, narration text, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "LPONo" character varying, "LPODate" timestamp without time zone, "AgainstNo" character varying, "orderMasterId" integer, "quotationMasterId" integer, "proformaMasterId" integer, "BatchId" character varying, "taxType" character varying, "totalAmount" numeric, "taxableAmt" numeric, "additionalCost" numeric, "totalTax" numeric, "billDiscount" numeric, "roundOff" numeric, othercharge numeric, "otherChargeLedgerId" integer, "taxTotal" numeric, "grandTotal" numeric, "transportCompany" character varying, "deliveryLocation" character varying, "driverName" character varying, "contactNo" character varying, "deliveryVehicleNo" character varying, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, "LedgerName" character varying, "PricingLevelName" character varying, "employeeName" character varying, "CostCentre" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        dnm."deliveryNoteMasterId",
        dnm."suffixPrefixId",
        dnm."voucherNo",
        dnm."deliveryNoteNo",
        dnm."date",
        dnm."ledgerId",
        dnm."customerName",
        dnm."CustomerAddress",
        dnm."CustomerPhone",
        dnm."CustomerVATNo",
        dnm."RefNo",
        dnm."RefDate",
        dnm."costCentreId",
        dnm."pricingLevelId",
        dnm."employeeId",
        dnm.narration,
        dnm."currencyConversionId",
        dnm."exchangeRate",
        dnm."exchangeDate",
        dnm."LPONo",
        dnm."LPODate",
        dnm."AgainstNo",
        dnm."orderMasterId",
        dnm."quotationMasterId",
        dnm."proformaMasterId",
        dnm."BatchId",
        dnm."taxType",
        dnm."totalAmount",
        dnm."taxableAmt",
        dnm."additionalCost",
        dnm."totalTax",
        dnm."billDiscount",
        dnm."roundOff",
        dnm."othercharge",
        dnm."otherChargeLedgerId",
        dnm."taxTotal",
        dnm."grandTotal",
        dnm."transportCompany",
        dnm."deliveryLocation",
        dnm."driverName",
        dnm."contactNo",
        dnm."deliveryVehicleNo",
        dnm."postedStatus",
        dnm."postedBy",
        dnm."postedDate",
        dnm."branchId",
        dnm."CreatedDate",
        dnm."CreatedUser",
        dnm."ModifiedDate",
        dnm."ModifiedUser",

        -- Joined descriptive fields
        l."ledgerName" AS "LedgerName",
        p."PricingLevelName",
        e."employeeName",
        c."CostCentre"
    FROM public."tbl_DeliveryNoteMaster" dnm
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = dnm."ledgerId"
    LEFT JOIN public."tbl_PricingLevel" p ON p."PricingLevelId" = dnm."pricingLevelId"
    LEFT JOIN public."tbl_Employee" e ON e."employeeId" = dnm."employeeId"
    LEFT JOIN public."tbl_CostCentre" c ON c."costCentreId" = dnm."costCentreId"
    WHERE dnm."deliveryNoteMasterId" = p_deliverynotemasterid;
END;
$$;


ALTER FUNCTION public.get_delivery_note_master_data_byid(p_deliverynotemasterid bigint) OWNER TO postgres;

--
-- TOC entry 409 (class 1255 OID 78696)
-- Name: get_delivery_note_proforma_details_with_remaining(bigint, bigint, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_proforma_details_with_remaining(p_proforma_master_id bigint, p_deliverynotemasterid bigint, p_branchid integer) RETURNS TABLE("proformaDetails1Id" bigint, "proformaMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "taxAmount" numeric, "grossAmount" numeric, "netAmount" numeric, amount numeric, "ConversionFactor" numeric, barcode text, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" text, "PurchaseRate" numeric, batch character varying, "productName" character varying, "taxName" numeric, "brandName" character varying, "unitName" character varying, "SlNo" integer, "Size" character varying, otherchargeonproduct numeric, "partNo" character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalpoint INT;
BEGIN
    -- Get decimal precision
    SELECT C."noOfDecimalPlace"
    INTO decimalpoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = p_branchid
    LIMIT 1;

    RETURN QUERY
    SELECT 
        A."proformaDetails1Id",
        A."proformaMasterId",
        A."productCode",

        -- Qty Calculation
        (
            CASE 
                WHEN ((A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(C."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0)
                    THEN (A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(C."qty"),0) - COALESCE(SUM(J."qty"),0))
                ELSE 0
            END
            +
            CASE 
                WHEN ((SELECT DN."proformaMasterId" FROM "tbl_DeliveryNoteMaster" DN WHERE DN."deliveryNoteMasterId" = p_deliverynotemasterid) = p_proforma_master_id)
                    THEN COALESCE(
                        (SELECT DND."qty" FROM "tbl_DeliveryNoteDetails1" DND 
                         WHERE DND."proformaDetails1Id" = A."proformaDetails1Id" 
                         AND DND."deliveryNoteMasterId" = p_deliverynotemasterid),
                        0
                    )
                ELSE 0
            END
        )::NUMERIC(24,6) AS "qty",

        -- Free Qty Calculation
        (
            CASE 
                WHEN ((A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(C."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0)
                    THEN (A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(C."freeQty"),0) - COALESCE(SUM(J."freeQty"),0))
                ELSE 0
            END
            +
            CASE 
                WHEN ((SELECT DN."proformaMasterId" FROM "tbl_DeliveryNoteMaster" DN WHERE DN."deliveryNoteMasterId" = p_deliverynotemasterid) = p_proforma_master_id)
                    THEN COALESCE(
                        (SELECT DND."freeQty" FROM "tbl_DeliveryNoteDetails1" DND 
                         WHERE DND."proformaDetails1Id" = A."proformaDetails1Id" 
                         AND DND."deliveryNoteMasterId" = p_deliverynotemasterid),
                        0
                    )
                ELSE 0
            END
        )::NUMERIC(24,6) AS "freeQty",

        ROUND((A."rate" / CC."rate")::NUMERIC, decimalpoint) AS "rate",
        A."unitId",
        ROUND((A."discountPercentage" / CC."rate")::NUMERIC, decimalpoint) AS "discountPercentage",
        A."taxId",
        A."taxType",
        ROUND((A."taxAmount" / CC."rate")::NUMERIC, decimalpoint) AS "taxAmount",
        ROUND((A."grossAmount" / CC."rate")::NUMERIC, decimalpoint) AS "grossAmount",
        ROUND((A."netAmount" / CC."rate")::NUMERIC, decimalpoint) AS "netAmount",
        ROUND((A."amount" / CC."rate")::NUMERIC, decimalpoint) AS "amount",
        A."ConversionFactor",
        A."barcode",
        ROUND((A."billDiscOnProduct" / CC."rate")::NUMERIC, decimalpoint) AS "billDiscOnProduct",
        ROUND((A."AddCostonProduct" / CC."rate")::NUMERIC, decimalpoint) AS "AddCostonProduct",
        A."productDescription",
        ROUND((A."PurchaseRate" / CC."rate")::NUMERIC, decimalpoint) AS "PurchaseRate",
        ''::varchar AS "batch",
        P."productName",
        T."rate" AS "taxName",
        Br."brandName",
        U."UnitName",
        A."SlNo",
        A."Size",
        ROUND((A."otherchargeonproduct" / CC."rate")::NUMERIC, decimalpoint) AS "otherchargeonproduct",
        COALESCE(P."partNo",'') AS "partNo"
    FROM "tbl_proformaInvoiceDetails1" A
    INNER JOIN "tbl_proformaInvoiceMaster" B ON A."proformaMasterId" = B."proformaMasterId"
    INNER JOIN "tbl_CurrencyConversion" CC ON B."currencyConversionId" = CC."currencyConversionId"
    INNER JOIN "tbl_Currency" CU ON CU."currencyId" = CC."currencyId"
    LEFT JOIN "tbl_DeliveryNoteMaster" E ON E."proformaMasterId" = B."proformaMasterId" 
    LEFT JOIN "tbl_DeliveryNoteDetails1" C ON C."deliveryNoteMasterId" = E."deliveryNoteMasterId" 
       AND C."quotationDetailsId"::BIGINT = A."quotationDetailsId"::BIGINT
    LEFT JOIN "tbl_SalesMaster" G ON G."proformaMasterId" = B."proformaMasterId" 
    LEFT JOIN "tbl_SalesDetails1" D ON D."salesMasterId" = G."salesMasterId" 
       AND D."quotationDetailsId"::BIGINT = A."quotationDetailsId"::BIGINT
    LEFT JOIN "tbl_SalesOrderMaster" I ON I."proformaMasterId" = B."proformaMasterId" 
    LEFT JOIN "tbl_SalesOrderdetails1" J ON J."orderMasterId" = I."orderMasterId" 
       AND J."quotationDetailsId"::BIGINT = A."quotationDetailsId"::BIGINT
    INNER JOIN "tbl_Product" P ON A."productCode" = P."productCode"
    INNER JOIN "tbl_Unit" U ON A."unitId" = U."unitId"
    INNER JOIN "tbl_TaxMaster" T ON A."taxId" = T."taxId"
    INNER JOIN "tbl_Brand" Br ON P."brandId" = Br."brandId"
    WHERE A."proformaMasterId" = p_proforma_master_id
    GROUP BY 
        A."proformaDetails1Id", A."proformaMasterId", A."productCode", A."unitId",
        A."qty", A."freeQty", A."rate", A."taxId", A."taxType", A."discountPercentage",
        A."billDiscOnProduct", A."otherchargeonproduct", A."ConversionFactor", A."taxAmount", A."grossAmount", 
        A."netAmount", A."amount", A."barcode", A."AddCostonProduct", A."productDescription", 
        A."PurchaseRate", A."SlNo", A."Size", P."productName", T."rate", Br."brandName", U."UnitName", 
        CC."rate", P."partNo"
    HAVING ( 
        (CASE WHEN ((A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(C."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0) 
            THEN (A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(C."qty"),0) - COALESCE(SUM(J."qty"),0))
            ELSE 0 END) > 0
        OR
        (CASE WHEN ((A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(C."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0)
            THEN (A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(C."freeQty"),0) - COALESCE(SUM(J."freeQty"),0))
            ELSE 0 END) > 0
    )
    ORDER BY A."SlNo";
END;
$$;


ALTER FUNCTION public.get_delivery_note_proforma_details_with_remaining(p_proforma_master_id bigint, p_deliverynotemasterid bigint, p_branchid integer) OWNER TO postgres;

--
-- TOC entry 396 (class 1255 OID 78500)
-- Name: get_delivery_note_proforma_list(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_proforma_list(p_ledgerid integer, p_branchid integer, p_deliverynotemasterid integer) RETURNS TABLE("proformaMasterId" bigint, "proformaNo" character varying, "ledgerName" character varying, "customerName" character varying, "CustomerAddress" text, "CustomerPhone" character varying, "ProformaDate" text, "totalAmount" numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    -- Get decimal precision for the branch's currency
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::INT = p_branchid;

    RETURN QUERY
    SELECT DISTINCT
        TEMP."proformaMasterId",
        TEMP."proformaNo",
        TEMP."ledgerName",
        TEMP."customerName",
        TEMP."CustomerAddress",
        TEMP."CustomerPhone",
        TO_CHAR(TEMP."ProformaDate", 'DD-Mon-YYYY') AS "ProformaDate",
        ROUND(TEMP."totalAmount", decimalPoint)::NUMERIC(24,6) AS "totalAmount",
        TEMP."ProformaDate"::DATE AS "date"
    FROM (
        SELECT 
            B."proformaDetails1Id",
            A."proformaMasterId",
            A."proformaNo",
            CASE
                WHEN ((B."qty"
                    - COALESCE(SUM(D."qty"),0)
                    - COALESCE(SUM(C."qty"),0)
                    - COALESCE(SUM(J."qty"),0)
                    ) > 0)
                THEN (B."qty"
                    - COALESCE(SUM(D."qty"),0)
                    - COALESCE(SUM(C."qty"),0)
                    - COALESCE(SUM(J."qty"),0)
                    )
                ELSE 0
            END
            +
            CASE 
                WHEN (
                    COALESCE((
                        SELECT DND."qty"
                        FROM public."tbl_DeliveryNoteDetails1" DND
                        WHERE DND."proformaDetails1Id" = B."proformaDetails1Id"
                        AND DND."deliveryNoteMasterId" = p_deliverynotemasterid
                        LIMIT 1
                    ),0)
                ) > 0
                THEN (
                    SELECT COALESCE(DND."qty",0)
                    FROM public."tbl_DeliveryNoteDetails1" DND
                    WHERE DND."proformaDetails1Id" = B."proformaDetails1Id"
                    AND DND."deliveryNoteMasterId" = p_deliverynotemasterid
                    LIMIT 1
                )
                ELSE 0
            END AS qty,

            CASE
                WHEN ((B."freeQty"
                    - COALESCE(SUM(D."freeQty"),0)
                    - COALESCE(SUM(C."freeQty"),0)
                    - COALESCE(SUM(J."freeQty"),0)
                    ) > 0)
                THEN (B."freeQty"
                    - COALESCE(SUM(D."freeQty"),0)
                    - COALESCE(SUM(C."freeQty"),0)
                    - COALESCE(SUM(J."freeQty"),0)
                    )
                ELSE 0
            END
            +
            CASE 
                WHEN (
                    COALESCE((
                        SELECT DND."freeQty"
                        FROM public."tbl_DeliveryNoteDetails1" DND
                        WHERE DND."proformaDetails1Id" = B."proformaDetails1Id"
                        AND DND."deliveryNoteMasterId" = p_deliverynotemasterid
                        LIMIT 1
                    ),0)
                ) > 0
                THEN (
                    SELECT COALESCE(DND."freeQty",0)
                    FROM public."tbl_DeliveryNoteDetails1" DND
                    WHERE DND."proformaDetails1Id" = B."proformaDetails1Id"
                    AND DND."deliveryNoteMasterId" = p_deliverynotemasterid
                    LIMIT 1
                )
                ELSE 0
            END AS "freeQty",

            A."partyName" AS "customerName",
            AL."ledgerName",
            A."partyAddress" AS "CustomerAddress",
            A."partyMobile" AS "CustomerPhone",
            A."date"::DATE AS "ProformaDate",
            A."totalAmount"

        FROM public."tbl_proformaInvoiceMaster" A
        INNER JOIN public."tbl_proformaInvoiceDetails1" B 
            ON A."proformaMasterId" = B."proformaMasterId"
        INNER JOIN public."tbl_AccountLedger" AL 
            ON AL."ledgerId" = A."ledgerId"

        LEFT JOIN public."tbl_DeliveryNoteMaster" E 
            ON E."proformaMasterId" = A."proformaMasterId"
        LEFT JOIN public."tbl_DeliveryNoteDetails1" C 
            ON C."deliveryNoteMasterId" = E."deliveryNoteMasterId"
            AND C."proformaDetails1Id" = B."proformaDetails1Id"

        LEFT JOIN public."tbl_SalesMaster" G 
            ON G."proformaMasterId" = A."proformaMasterId"
        LEFT JOIN public."tbl_SalesDetails1" D 
            ON D."salesMasterId" = G."salesMasterId"
            AND D."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"::BIGINT


        LEFT JOIN public."tbl_SalesOrderMaster" I 
            ON I."proformaMasterId" = A."proformaMasterId"
        LEFT JOIN public."tbl_SalesOrderdetails1" J 
            ON J."orderMasterId" = I."orderMasterId"
            AND J."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"::BIGINT

        WHERE 
            A."branchId" = p_branchid
            AND ( A."ledgerId" = p_ledgerid::BIGINT)

        GROUP BY 
            B."proformaDetails1Id", A."proformaMasterId", A."proformaNo",
            B."qty", B."freeQty",
            A."partyName", AL."ledgerName", A."partyAddress", A."partyMobile",
            A."date", A."totalAmount"
    ) AS TEMP
    WHERE TEMP.qty > 0 OR TEMP."freeQty" > 0
    ORDER BY TEMP."ProformaDate" DESC, TEMP."proformaMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_delivery_note_proforma_list(p_ledgerid integer, p_branchid integer, p_deliverynotemasterid integer) OWNER TO postgres;

--
-- TOC entry 360 (class 1255 OID 78532)
-- Name: get_delivery_note_proforma_master_byid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_proforma_master_byid(p_proformamasterid integer) RETURNS TABLE("proformaMasterId" bigint, "voucherNo" character varying, "proformaNo" character varying, "branchId" integer, "suffixPrefixId" integer, date text, "dueDate" text, "ledgerId" integer, narration text, "currencyConversionId" integer, "totalAmount" numeric, "currencyId" integer, "noOfDecimalPlace" integer, "Refer" character varying, "pricingLevelId" integer, "employeeId" integer, "quotationMasterId" integer, "additionalCost" numeric, "billDiscount" numeric, "taxType" character varying, "lrNo" character varying, "transportCompany" character varying, "costCentreId" integer, "partyName" character varying, "partyAddress" text, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "roundOff" numeric, "ledgerName" character varying, "currencyName" character varying, "CostCentre" character varying, "totalTax" numeric, status character varying, othercharge numeric, "taxableAmt" numeric, "subTotal" numeric, "AgainstNo" character varying, "exchangeRate" numeric, "exchangeDate" text, "otherChargeLedgerId" character varying, "otherChargeLedgerName" character varying, "LPONO" character varying, "LPODate" text, "deliveryDate" text, "BatchId" character varying, "PaymentTerms" character varying, "DeliveryTerms" character varying, "AdvancePerc" numeric)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        A."proformaMasterId",
        A."voucherNo",
        A."proformaNo",
        A."branchId",
        A."suffixPrefixId",
        TO_CHAR(A."date", 'DD-Mon-YYYY') AS "date",
        TO_CHAR(A."dueDate", 'DD-Mon-YYYY') AS "dueDate",
        A."ledgerId",
        A."narration",
        A."currencyConversionId",
        ROUND((A."totalAmount"/B."rate")::NUMERIC, C."noOfDecimalPlace") AS "totalAmount",
        B."currencyId",
        C."noOfDecimalPlace",
        -- ✅ Explicitly cast CASE result to VARCHAR
        (CASE 
            WHEN (
                (SELECT COUNT(*) FROM "tbl_SalesOrderMaster" SO WHERE SO."proformaMasterId" = A."proformaMasterId") +
                (SELECT COUNT(*) FROM "tbl_DeliveryNoteMaster" D WHERE D."proformaMasterId" = A."proformaMasterId") +
                (SELECT COUNT(*) FROM "tbl_SalesMaster" E WHERE E."proformaMasterId" = A."proformaMasterId")
            ) = 0 THEN 'n'::VARCHAR ELSE 'y'::VARCHAR
        END) AS "Refer",
        A."pricingLevelId",
        A."employeeId",
        A."quotationMasterId",
        ROUND((A."additionalCost"/B."rate")::NUMERIC, C."noOfDecimalPlace") AS "additionalCost",
        ROUND((A."billDiscount"/B."rate")::NUMERIC, C."noOfDecimalPlace") AS "billDiscount",
        A."taxType",
        A."lrNo",
        A."transportCompany",
        A."costCentreId",
        A."partyName",
        A."partyAddress",
        A."partyMobile",
        A."partyVatNo",
        A."partyRefNo",
        A."partyRefDate",
        ROUND((A."roundOff"/B."rate")::NUMERIC, C."noOfDecimalPlace") AS "roundOff",
        L."ledgerName",
        C."currencyName",
        CC."CostCentre",
        ROUND((A."totalTax"/B."rate")::NUMERIC, C."noOfDecimalPlace") AS "totalTax",
        A."status",
        ROUND((A."othercharge"/B."rate")::NUMERIC, C."noOfDecimalPlace") AS "othercharge",
        ROUND((COALESCE(A."taxableAmt",0)/B."rate")::NUMERIC, C."noOfDecimalPlace") AS "taxableAmt",
        ROUND((COALESCE(A."subTotal",0)/B."rate")::NUMERIC, C."noOfDecimalPlace") AS "subTotal",
        COALESCE(A."AgainstNo",'') AS "AgainstNo",
        COALESCE(A."exchangeRate",0) AS "exchangeRate",
        TO_CHAR(A."exchangeDate", 'DD-Mon-YYYY') AS "exchangeDate",
        COALESCE(A."otherChargeLedgerId",'0') AS "otherChargeLedgerId",
        COALESCE(AL."ledgerName",'') AS "otherChargeLedgerName",
        COALESCE(A."LPONO",'') AS "LPONO",
        TO_CHAR(A."LPODate", 'DD-Mon-YYYY') AS "LPODate",
        TO_CHAR(A."deliveryDate", 'DD-Mon-YYYY') AS "deliveryDate",
        COALESCE(A."BatchId",'') AS "BatchId",
        COALESCE(A."PaymentTerms",'') AS "PaymentTerms",
        COALESCE(A."DeliveryTerms",'') AS "DeliveryTerms",
        COALESCE(A."AdvancePerc",0) AS "AdvancePerc"
    FROM "tbl_proformaInvoiceMaster" A
        INNER JOIN "tbl_CurrencyConversion" B ON A."currencyConversionId" = B."currencyConversionId"
        INNER JOIN "tbl_Currency" C ON B."currencyId" = C."currencyId"
        INNER JOIN "tbl_AccountLedger" L ON A."ledgerId" = L."ledgerId"
        INNER JOIN "tbl_CostCentre" CC ON A."costCentreId" = CC."costCentreId"
        LEFT JOIN "tbl_AccountLedger" AL ON A."otherChargeLedgerId"::BIGINT = AL."ledgerId"
    WHERE A."proformaMasterId" = p_proformamasterid;
END;
$$;


ALTER FUNCTION public.get_delivery_note_proforma_master_byid(p_proformamasterid integer) OWNER TO postgres;

--
-- TOC entry 317 (class 1255 OID 78767)
-- Name: get_delivery_note_sales_order_details_with_remaining(integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_sales_order_details_with_remaining(p_ordermasterid integer, p_deliverynotemasterid integer) RETURNS TABLE("orderDetails1Id" bigint, "orderMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "quotationDetailsId" integer, "ConversionFactor" numeric, barcode text, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "grossAmount" numeric, "netAmount" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "taxAmount" numeric, "productName" character varying, "taxName" numeric, "brandName" character varying, "UnitName" character varying, "SlNo" integer, otherchargeonproduct numeric, "partNo" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        A."orderDetails1Id",
        A."orderMasterId",
        A."productCode",

        -- Qty Calculation
        CASE 
            WHEN ROUND((A."qty" - COALESCE(SUM(E."qty"),0) - COALESCE(SUM(G."qty"),0)),2) > 0 
            THEN ROUND((A."qty" - COALESCE(SUM(E."qty"),0) - COALESCE(SUM(G."qty"),0)),2)
            ELSE 0 
        END
        +
        CASE 
            WHEN ((SELECT F2."orderMasterId" FROM "tbl_DeliveryNoteMaster" F2 WHERE F2."deliveryNoteMasterId" = p_deliverynotemasterid) = p_ordermasterid)
            THEN COALESCE((SELECT E2."qty" FROM "tbl_DeliveryNoteDetails1" E2 
                           WHERE E2."orderDetails1Id" = A."orderDetails1Id" 
                             AND E2."deliveryNoteMasterId" = p_deliverynotemasterid), 0)
            ELSE 0 
        END
        AS "qty",

        -- Free Qty Calculation
        CASE 
            WHEN ROUND((A."freeQty" - COALESCE(SUM(E."freeQty"),0) - COALESCE(SUM(G."freeQty"),0)),2) > 0 
            THEN ROUND((A."freeQty" - COALESCE(SUM(E."freeQty"),0) - COALESCE(SUM(G."freeQty"),0)),2)
            ELSE 0 
        END
        +
        CASE 
            WHEN ((SELECT F3."orderMasterId" FROM "tbl_DeliveryNoteMaster" F3 WHERE F3."deliveryNoteMasterId" = p_deliverynotemasterid) = p_ordermasterid)
            THEN COALESCE((SELECT E3."freeQty" FROM "tbl_DeliveryNoteDetails1" E3 
                           WHERE E3."orderDetails1Id" = A."orderDetails1Id" 
                             AND E3."deliveryNoteMasterId" = p_deliverynotemasterid), 0)
            ELSE 0 
        END
        AS "freeQty",

        ROUND(A."rate" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "rate",
        A."unitId",
        ROUND(A."amount" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "amount",
        A."quotationDetailsId",
        A."ConversionFactor",
        A."barcode"::TEXT,
        ROUND(A."discountPercentage" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "discountPercentage",
        A."taxId",
        A."taxType",
        ROUND(A."grossAmount" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "grossAmount",
        ROUND(A."netAmount" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "netAmount",
        ROUND(A."billDiscOnProduct" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "billDiscOnProduct",
        ROUND(A."AddCostonProduct" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "AddCostonProduct",
        A."productDescription",
        ROUND(A."taxAmount" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "taxAmount",
        P."productName",
        T."rate" AS "taxName",
        Br."brandName",
        U."UnitName",
        A."SlNo",
        ROUND(A."otherchargeonproduct" / C."rate", D."noOfDecimalPlace")::NUMERIC(24,6) AS "otherchargeonproduct",
        COALESCE(P."partNo", '') AS "partNo"

    FROM "tbl_SalesOrderdetails1" A
    INNER JOIN "tbl_SalesOrderMaster" B ON A."orderMasterId" = B."orderMasterId"
    INNER JOIN "tbl_CurrencyConversion" C ON B."currencyConversionId" = C."currencyConversionId"
    INNER JOIN "tbl_Currency" D ON C."currencyId" = D."currencyId"
    INNER JOIN "tbl_Product" P ON A."productCode" = P."productCode"
    INNER JOIN "tbl_Unit" U ON A."unitId" = U."unitId"
    INNER JOIN "tbl_TaxMaster" T ON A."taxId" = T."taxId"
    INNER JOIN "tbl_Brand" Br ON P."brandId" = Br."brandId"
    LEFT JOIN "tbl_DeliveryNoteMaster" F ON F."orderMasterId" = B."orderMasterId"
    LEFT JOIN "tbl_DeliveryNoteDetails1" E ON E."deliveryNoteMasterId" = F."deliveryNoteMasterId" 
                                           AND A."orderDetails1Id" = E."orderDetails1Id"
    LEFT JOIN "tbl_SalesMaster" H ON H."orderMasterId" = B."orderMasterId"
    LEFT JOIN "tbl_SalesDetails1" G ON G."salesMasterId" = H."salesMasterId" 
                                    AND A."orderDetails1Id" = G."orderDetails1Id"::BIGINT
    WHERE A."orderMasterId" = p_ordermasterid
    GROUP BY 
        A."orderDetails1Id", A."orderMasterId", A."productCode", A."unitId", 
        A."qty", A."freeQty", A."rate", C."rate", D."noOfDecimalPlace", A."amount",
        A."quotationDetailsId", A."ConversionFactor", A."barcode",
         A."discountPercentage", A."taxId", A."taxType", 
        A."grossAmount", A."netAmount", A."billDiscOnProduct", A."AddCostonProduct",
        A."productDescription", A."taxAmount", P."productName", T."rate", 
        Br."brandName", U."UnitName", A."SlNo", 
        A."otherchargeonproduct", P."partNo"
    HAVING 
        (ROUND((A."qty" - COALESCE(SUM(E."qty"),0) - COALESCE(SUM(G."qty"),0)),2) > 0)
        OR
        (ROUND((A."freeQty" - COALESCE(SUM(E."freeQty"),0) - COALESCE(SUM(G."freeQty"),0)),2) > 0);
END;
$$;


ALTER FUNCTION public.get_delivery_note_sales_order_details_with_remaining(p_ordermasterid integer, p_deliverynotemasterid integer) OWNER TO postgres;

--
-- TOC entry 310 (class 1255 OID 78700)
-- Name: get_delivery_note_sales_order_list(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_sales_order_list(p_ledgerid integer, p_branchid integer, p_deliverynotemasterid integer) RETURNS TABLE("orderMasterId" bigint, "orderNo" character varying, "ledgerName" character varying, "customerName" character varying, "CustomerAddress" character varying, "CustomerPhone" character varying, "OrderDate" text, "totalAmount" numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalpoint INT;
BEGIN
    -- Get decimal precision 
    SELECT C."noOfDecimalPlace"
    INTO decimalpoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = p_branchid;

    RETURN QUERY
    SELECT DISTINCT 
        TEMP."orderMasterId",
        TEMP."orderNo",
        TEMP."ledgerName",
        TEMP."customerName",
        TEMP."CustomerAddress",
        TEMP."CustomerPhone",
        TO_CHAR(TEMP."OrderDate", 'DD-Mon-YYYY') AS "OrderDate",
        ROUND(TEMP."totalAmount", decimalpoint)::NUMERIC(24,2) AS "totalAmount",
        TEMP."OrderDate"::DATE AS "date"
    FROM (
        SELECT 
            B."orderDetails1Id",
            A."orderMasterId",
            A."orderNo",
            -- Quantity calculation
            (
                CASE 
                    WHEN ((B."qty" - COALESCE(SUM(C."qty"),0)  - COALESCE(SUM(D."qty"),0)) > 0)
                    THEN (B."qty" - COALESCE(SUM(C."qty"),0)  - COALESCE(SUM(D."qty"),0))
                    ELSE 0 
                END
                +
                COALESCE((
                    SELECT "qty"
                    FROM "tbl_DeliveryNoteDetails1" SD1
                    WHERE SD1."orderDetails1Id" = B."orderDetails1Id"
                      AND SD1."deliveryNoteMasterId" = p_deliverynotemasterid::BIGINT
                    LIMIT 1
                ), 0)
            ) AS "qty",

            -- Free Qty calculation
            (
                CASE 
                    WHEN ((B."freeQty" - COALESCE(SUM(C."freeQty"),0)  - COALESCE(SUM(D."freeQty"),0)) > 0)
                    THEN (B."freeQty" - COALESCE(SUM(C."freeQty"),0)  - COALESCE(SUM(D."freeQty"),0))
                    ELSE 0
                END
                +
                COALESCE((
                    SELECT "freeQty"
                    FROM "tbl_DeliveryNoteDetails1" SD1
                    WHERE SD1."orderDetails1Id" = B."orderDetails1Id"
                      AND SD1."deliveryNoteMasterId" = p_deliverynotemasterid::BIGINT
                    LIMIT 1
                ), 0)
            ) AS "freeQty",

            A."partyName" AS "customerName",
            AL."ledgerName",
            A."partyAddress" AS "CustomerAddress",
            A."partyMobile" AS "CustomerPhone",
            A."date"::DATE AS "OrderDate",
            A."totalAmount"
        FROM "tbl_SalesOrderMaster" A
        INNER JOIN "tbl_SalesOrderdetails1" B ON A."orderMasterId" = B."orderMasterId"
        INNER JOIN "tbl_AccountLedger" AL ON AL."ledgerId" = A."ledgerId"
        LEFT JOIN "tbl_DeliveryNoteMaster" E ON E."orderMasterId" = A."orderMasterId" 
           
        LEFT JOIN "tbl_DeliveryNoteDetails1" C 
            ON C."deliveryNoteMasterId" = E."deliveryNoteMasterId" 
           AND C."orderDetails1Id" = B."orderDetails1Id"
        LEFT JOIN "tbl_SalesMaster" G ON G."orderMasterId" = A."orderMasterId"
        LEFT JOIN "tbl_SalesDetails1" D 
            ON D."salesMasterId" = G."salesMasterId"
           AND B."orderDetails1Id" = D."orderDetails1Id"::BIGINT

        WHERE 
            A."branchId" = p_branchid::INT
            AND ( A."ledgerId" = p_ledgerid::INT)
        GROUP BY 
            B."orderDetails1Id", A."orderMasterId", A."orderNo", B."qty", B."freeQty",
            A."partyName", AL."ledgerName", A."partyAddress", A."partyMobile", A."date", A."totalAmount"
    ) AS TEMP
    WHERE TEMP."qty" > 0 OR TEMP."freeQty" > 0
    ORDER BY TEMP."OrderDate" DESC, TEMP."orderMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_delivery_note_sales_order_list(p_ledgerid integer, p_branchid integer, p_deliverynotemasterid integer) OWNER TO postgres;

--
-- TOC entry 407 (class 1255 OID 78744)
-- Name: get_delivery_note_sales_order_master_byid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_sales_order_master_byid(p_ordermasterid integer) RETURNS TABLE("orderMasterId" bigint, "voucherNo" character varying, "orderNo" character varying, "userId" character varying, "branchId" integer, "suffixPrefixId" integer, date text, "dueDate" text, "ledgerId" integer, narration text, "currencyConversionId" integer, "totalAmount" numeric, "currencyId" integer, "noOfDecimalPlace" integer, "Refer" text, "pricingLevelId" integer, "employeeId" integer, "quotationMasterId" integer, "additionalCost" numeric, "billDiscount" numeric, "taxType" character varying, "lrNo" character varying, "transportCompany" character varying, "costCentreId" integer, "partyName" character varying, "partyAddress" character varying, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "roundOff" numeric, "ledgerName" character varying, "currencyName" character varying, "CostCentre" character varying, "totalTax" numeric, status character varying, "otherCharge" numeric, "proformaMasterId" integer, "taxableAmt" numeric, "subTotal" numeric, "AgainstNo" character varying, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "otherChargeLedgerId" character varying, "otherChargeLedgerName" character varying, "LPONO" character varying, "LPODate" timestamp without time zone)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_rate NUMERIC(18,6);
    v_noOfDecimal INT;
BEGIN
    RETURN QUERY
    SELECT 
        A."orderMasterId",
        A."voucherNo",
        A."orderNo",
        A."CreatedUser" AS "userId",
        A."branchId",
        A."suffixPrefixId",
        TO_CHAR(A."date", 'DD-Mon-YYYY') AS "date",
        TO_CHAR(A."dueDate", 'DD-Mon-YYYY') AS "dueDate",
        A."ledgerId",
        A."narration",
        A."currencyConversionId",
        ROUND((A."totalAmount" / COALESCE(B."rate",1)), C."noOfDecimalPlace")::NUMERIC(24,6),
        B."currencyId",
        C."noOfDecimalPlace",
        CASE 
            WHEN ((SELECT COUNT(*) FROM "tbl_DeliveryNoteMaster" D WHERE D."orderMasterId" = A."orderMasterId") +
                  (SELECT COUNT(*) FROM "tbl_SalesMaster" E WHERE E."orderMasterId" = A."orderMasterId")) = 0
            THEN 'n' ELSE 'y' 
        END AS "Refer",
        A."pricingLevelId",
        A."employeeId",
        A."quotationMasterId",
        ROUND((A."additionalCost"/COALESCE(B."rate",1)), C."noOfDecimalPlace"),
        ROUND((A."billDiscount"/COALESCE(B."rate",1)), C."noOfDecimalPlace"),
        A."taxType",
        A."lrNo",
        A."transportCompany",
        A."costCentreId",
        A."partyName",
        A."partyAddress",
        A."partyMobile",
        A."partyVatNo",
        A."partyRefNo",
        A."partyRefDate",
        ROUND((A."roundOff"/COALESCE(B."rate",1)), C."noOfDecimalPlace"),
        L."ledgerName",
        C."currencyName",
        CC."CostCentre",
        ROUND((A."totalTax"/COALESCE(B."rate",1)), C."noOfDecimalPlace"),
        A."status",
        ROUND((A."othercharge"/COALESCE(B."rate",1)), C."noOfDecimalPlace"),
        A."proformaMasterId",
        ROUND((COALESCE(A."taxableAmt",0)/COALESCE(B."rate",1)), C."noOfDecimalPlace"),
        ROUND((COALESCE(A."subTotal",0)/COALESCE(B."rate",1)), C."noOfDecimalPlace"),
        COALESCE(A."AgainstNo",''),
        COALESCE(A."exchangeRate",1),
        A."exchangeDate",
        COALESCE(A."otherChargeLedgerId",'')::VARCHAR,
        COALESCE(AL."ledgerName",''),
        COALESCE(A."LPONO",''),
        A."LPODate"
    FROM "tbl_SalesOrderMaster" A
    INNER JOIN "tbl_CurrencyConversion" B ON A."currencyConversionId" = B."currencyConversionId"
    INNER JOIN "tbl_Currency" C ON B."currencyId" = C."currencyId"
    INNER JOIN "tbl_AccountLedger" L ON A."ledgerId" = L."ledgerId"
    LEFT JOIN "tbl_CostCentre" CC ON A."costCentreId" = CC."costCentreId"
    LEFT JOIN "tbl_AccountLedger" AL ON A."otherChargeLedgerId"::BIGINT = AL."ledgerId"
    WHERE A."orderMasterId" = p_ordermasterid;
END;
$$;


ALTER FUNCTION public.get_delivery_note_sales_order_master_byid(p_ordermasterid integer) OWNER TO postgres;

--
-- TOC entry 309 (class 1255 OID 78494)
-- Name: get_delivery_note_sales_quotation_details_with_remaining(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_sales_quotation_details_with_remaining(p_quotation_master_id integer, p_delivery_note_master_id integer, p_branchid integer) RETURNS TABLE("quotationDetailsId" bigint, "quotationMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "taxAmount" numeric, "grossAmount" numeric, "netAmount" numeric, amount numeric, "ConversionFactor" numeric, barcode character varying, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "PurchaseRate" numeric, "productName" character varying, "taxName" numeric, "brandName" character varying, "UnitName" character varying, "SlNo" integer, otherchargeonproduct numeric, "partNo" character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    -- Get decimal precision for branch's currency
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::INT = p_branchid;

    RETURN QUERY
    SELECT 
        A."quotationDetailsId",
        A."quotationMasterId",
        A."productCode",

        -- Remaining qty calculation
        (
            CASE WHEN (A."qty"
                - COALESCE(SUM(D."qty"),0)
                - COALESCE(SUM(CD."qty"),0)
                - COALESCE(SUM(J."qty"),0)
            ) > 0
            THEN (A."qty"
                - COALESCE(SUM(D."qty"),0)
                - COALESCE(SUM(CD."qty"),0)
                - COALESCE(SUM(J."qty"),0)
            )
            ELSE 0 END
        )
        + CASE
            WHEN ((SELECT M."quotationMasterId" 
                   FROM public."tbl_DeliveryNoteMaster" M
                   WHERE M."deliveryNoteMasterId" = p_delivery_note_master_id) = p_quotation_master_id)
            THEN COALESCE(
                (SELECT DND."qty" 
                 FROM public."tbl_DeliveryNoteDetails1" DND
                 WHERE DND."quotationDetailsId" = A."quotationDetailsId"
                 AND DND."deliveryNoteMasterId" = p_delivery_note_master_id
                 LIMIT 1), 0)
            ELSE 0
          END AS qty,

        -- FreeQty calculation
        (
            CASE WHEN (A."freeQty"
                - COALESCE(SUM(D."freeQty"),0)
                - COALESCE(SUM(CD."freeQty"),0)
                - COALESCE(SUM(J."freeQty"),0)
            ) > 0
            THEN (A."freeQty"
                - COALESCE(SUM(D."freeQty"),0)
                - COALESCE(SUM(CD."freeQty"),0)
                - COALESCE(SUM(J."freeQty"),0)
            )
            ELSE 0 END
        )
        + CASE
            WHEN ((SELECT M."quotationMasterId"
                   FROM public."tbl_DeliveryNoteMaster" M
                   WHERE M."deliveryNoteMasterId" = p_delivery_note_master_id) = p_quotation_master_id)
            THEN COALESCE(
                (SELECT DND."freeQty" 
                 FROM public."tbl_DeliveryNoteDetails1" DND
                 WHERE DND."quotationDetailsId" = A."quotationDetailsId"
                 AND DND."deliveryNoteMasterId" = p_delivery_note_master_id
                 LIMIT 1), 0)
            ELSE 0
          END AS "freeQty",

        ROUND(A."rate"/CC."rate", decimalPoint) AS rate,
        A."unitId",
        ROUND(A."discountPercentage"/CC."rate", decimalPoint) AS "discountPercentage",
        A."taxId",
        A."taxType",
        ROUND(A."taxAmount"/CC."rate", decimalPoint) AS "taxAmount",
        ROUND(A."grossAmount"/CC."rate", decimalPoint) AS "grossAmount",
        ROUND(A."netAmount"/CC."rate", decimalPoint) AS "netAmount",
        ROUND(A."amount"/CC."rate", decimalPoint) AS "amount",
        COALESCE(A."ConversionFactor",0) AS "ConversionFactor",
        A."barcode",
        ROUND(A."billDiscOnProduct"/CC."rate", decimalPoint),
        ROUND(A."AddCostonProduct"/CC."rate", decimalPoint),
        A."productDescription",
        ROUND(A."PurchaseRate"/CC."rate", decimalPoint) AS "PurchaseRate",
        P."productName",
        T."rate" AS "taxName",
        Bn."brandName",
        U."UnitName",
        A."SlNo",
        ROUND(A."otherchargeonproduct"/CC."rate", decimalPoint) AS "otherchargeonproduct",
        COALESCE(P."partNo",'') AS "partNo"

    FROM public."tbl_QuotationDetails" A
    INNER JOIN public."tbl_QuotationMaster" QM 
        ON A."quotationMasterId" = QM."quotationMasterId"
    INNER JOIN public."tbl_CurrencyConversion" CC 
        ON QM."currencyConversionId" = CC."currencyConversionId"
    INNER JOIN public."tbl_Currency" CU 
        ON CU."currencyId" = CC."currencyId"

    LEFT JOIN public."tbl_DeliveryNoteMaster" E 
        ON E."quotationMasterId" = QM."quotationMasterId"
    LEFT JOIN public."tbl_DeliveryNoteDetails1" CD 
        ON CD."deliveryNoteMasterId" = E."deliveryNoteMasterId" 
       AND CD."quotationDetailsId"::BIGINT = A."quotationDetailsId"

    LEFT JOIN public."tbl_SalesMaster" SM 
        ON SM."quotationMasterId" = QM."quotationMasterId"
    LEFT JOIN public."tbl_SalesDetails1" D 
        ON D."salesMasterId"::BIGINT = SM."salesMasterId"
       AND D."quotationDetailsId"::BIGINT = A."quotationDetailsId"

    LEFT JOIN public."tbl_SalesOrderMaster" SO 
        ON SO."quotationMasterId" = QM."quotationMasterId"
    LEFT JOIN public."tbl_SalesOrderdetails1" J 
        ON J."orderMasterId"::BIGINT = SO."orderMasterId"
       AND J."quotationDetailsId"::BIGINT = A."quotationDetailsId"

    INNER JOIN public."tbl_Product" P 
        ON A."productCode" = P."productCode"
    INNER JOIN public."tbl_Unit" U 
        ON A."unitId" = U."unitId"
    INNER JOIN public."tbl_TaxMaster" T 
        ON A."taxId" = T."taxId"
    INNER JOIN public."tbl_Brand" Bn 
        ON P."brandId" = Bn."brandId"

    WHERE A."quotationMasterId" = p_quotation_master_id
    GROUP BY 
        A."quotationDetailsId", A."quotationMasterId", A."productCode", A."unitId",
         A."qty", A."freeQty", A."rate",
        A."taxId", A."taxType", A."discountPercentage", A."billDiscOnProduct",
        A."otherchargeonproduct", A."ConversionFactor", A."taxAmount", A."grossAmount",
        A."netAmount", A."amount", A."barcode", A."AddCostonProduct",
         A."productDescription", A."PurchaseRate", A."SlNo",
         P."productName", T."rate",
        Bn."brandName", U."UnitName", CC."rate", P."partNo"
    HAVING 
        (CASE WHEN (A."qty"
            - COALESCE(SUM(D."qty"),0)
            - COALESCE(SUM(CD."qty"),0)
            - COALESCE(SUM(J."qty"),0)
            ) > 0 THEN TRUE ELSE FALSE END)
        OR (CASE WHEN (A."freeQty"
            - COALESCE(SUM(D."freeQty"),0)
            - COALESCE(SUM(CD."freeQty"),0)
            - COALESCE(SUM(J."freeQty"),0)
            ) > 0 THEN TRUE ELSE FALSE END)
    ORDER BY A."SlNo";
END;
$$;


ALTER FUNCTION public.get_delivery_note_sales_quotation_details_with_remaining(p_quotation_master_id integer, p_delivery_note_master_id integer, p_branchid integer) OWNER TO postgres;

--
-- TOC entry 347 (class 1255 OID 78401)
-- Name: get_delivery_note_sales_quotation_list(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_sales_quotation_list(in_ledgerid integer, in_branchid integer, in_deliverynotemasterid integer) RETURNS TABLE(quotationmasterid bigint, quotationno character varying, ledgername character varying, customername character varying, customeraddress text, customerphone character varying, quotationdate character varying, totalamount numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalpoint int;
BEGIN
    -- Get decimal precision from currency link
    SELECT C."noOfDecimalPlace"
    INTO decimalpoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = in_branchid;

    RETURN QUERY
    SELECT DISTINCT
        TEMP."quotationMasterId",
        TEMP."quotationNo",
        TEMP."ledgerName",
        TEMP."customerName",
        TEMP."CustomerAddress",
        TEMP."CustomerPhone",
        TO_CHAR(TEMP."QuotationDate", 'DD-Mon-YYYY')::varchar AS "QuotationDate",
        ROUND(TEMP."totalAmount", decimalpoint)::numeric(24,6) AS "totalAmount",
        TEMP."QuotationDate"::date AS date
    FROM (
        SELECT
            b."quotationDetailsId",
            a."quotationMasterId",
            a."quotationNo",
            (
                CASE 
                    WHEN ((b."qty" - COALESCE(SUM(d."qty"),0) - COALESCE(SUM(c."qty"),0) - COALESCE(SUM(j."qty"),0)) > 0)
                    THEN (b."qty" - COALESCE(SUM(d."qty"),0) - COALESCE(SUM(c."qty"),0) - COALESCE(SUM(j."qty"),0))
                    ELSE 0
                END +
                COALESCE(
                    (SELECT "qty" 
                     FROM public."tbl_DeliveryNoteDetails1" sd1 
                     WHERE sd1."quotationDetailsId"::bigint = b."quotationDetailsId"
                       AND sd1."deliveryNoteMasterId" = in_deliverynotemasterid),
                    0
                )
            ) AS qty,
            (
                CASE 
                    WHEN ((b."freeQty" - COALESCE(SUM(d."freeQty"),0) - COALESCE(SUM(c."freeQty"),0) - COALESCE(SUM(j."freeQty"),0)) > 0)
                    THEN (b."freeQty" - COALESCE(SUM(d."freeQty"),0) - COALESCE(SUM(c."freeQty"),0) - COALESCE(SUM(j."freeQty"),0))
                    ELSE 0
                END +
                COALESCE(
                    (SELECT "freeQty" 
                     FROM public."tbl_DeliveryNoteDetails1" sd1 
                     WHERE sd1."quotationDetailsId"::bigint = b."quotationDetailsId"
                       AND sd1."deliveryNoteMasterId" = in_deliverynotemasterid),
                    0
                )
            ) AS freeqty,
            a."customerName",
            al."ledgerName",
            a."CustomerAddress",
            a."CustomerPhone",
            a.date::date AS "QuotationDate",
            a."totalAmount"
        FROM public."tbl_QuotationMaster" a
        INNER JOIN public."tbl_QuotationDetails" b 
            ON a."quotationMasterId" = b."quotationMasterId"
        INNER JOIN public."tbl_AccountLedger" al 
            ON al."ledgerId" = a."ledgerId"
        LEFT JOIN public."tbl_DeliveryNoteMaster" e 
            ON e."quotationMasterId" = a."quotationMasterId"
        LEFT JOIN public."tbl_DeliveryNoteDetails1" c 
            ON c."deliveryNoteMasterId" = e."deliveryNoteMasterId"
           AND c."quotationDetailsId"::bigint = b."quotationDetailsId"
        LEFT JOIN public."tbl_SalesMaster" g 
            ON g."quotationMasterId" = a."quotationMasterId"
        LEFT JOIN public."tbl_SalesDetails1" d 
            ON d."salesMasterId" = g."salesMasterId"
           AND d."quotationDetailsId"::bigint = b."quotationDetailsId"
        LEFT JOIN public."tbl_SalesOrderMaster" i 
            ON i."quotationMasterId" = a."quotationMasterId"
        LEFT JOIN public."tbl_SalesOrderdetails1" j 
            ON j."orderMasterId" = i."orderMasterId"
           AND j."quotationDetailsId"::bigint = b."quotationDetailsId"
        WHERE a."branchId" = in_branchid
          AND a."ledgerId" = in_ledgerid
          AND a."approved" = 'True'
        GROUP BY 
            b."quotationDetailsId",
            a."quotationMasterId",
            a."quotationNo",
            b."qty",
            b."freeQty",
            a."customerName",
            al."ledgerName",
            a."CustomerAddress",
            a."CustomerPhone",
            a.date,
            a."totalAmount"
    ) AS TEMP
    WHERE TEMP.qty > 0 OR TEMP.freeqty > 0
    ORDER BY TEMP."QuotationDate" DESC, TEMP."quotationMasterId" DESC;

END;
$$;


ALTER FUNCTION public.get_delivery_note_sales_quotation_list(in_ledgerid integer, in_branchid integer, in_deliverynotemasterid integer) OWNER TO postgres;

--
-- TOC entry 369 (class 1255 OID 78461)
-- Name: get_delivery_note_sales_quotation_master_byid(integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_delivery_note_sales_quotation_master_byid(p_quotation_master_id integer, p_branchid integer) RETURNS TABLE("quotationMasterId" bigint, "voucherNo" character varying, "quotationNo" character varying, "pricingLevelId" integer, "ledgerId" integer, "employeeId" integer, "branchId" integer, "suffixPrefixId" integer, approved boolean, date text, "totalAmount" numeric, "grandTotal" numeric, "taxType" character varying, narration text, refer character varying, "additionalCost" numeric, "totalTax" numeric, "billDiscount" numeric, "billDiscOnProductStatus" boolean, "AddCostonProductStatus" boolean, "currencyConversionId" integer, "creditPeriod" integer, "roundOff" numeric, "customerName" character varying, "customerAddress" text, "customerPhone" character varying, "costCentreId" integer, "ledgerName" character varying, "currencyName" character varying, "costCentreName" character varying, "OtherCharge" numeric, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, contactperson character varying, contactno character varying, quatationvalidity character varying, deliveredwithin character varying, paymentterms character varying, deliverysite character varying, "taxableAmt" numeric, "Salesman" character varying, "SalesmanPhone" character varying, "currencyId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "otherChargeLedgerId" character varying, "otherChargeLedgerName" character varying, "partyName" character varying, "PartyAdditionalNo" character varying, "PartyStreetName" character varying, "PartyPostboxNo" character varying, "PartyCityName" character varying, "PartyCountry" character varying, "PartyBuildingNo" character varying, "PartyDistrict" character varying, "CarMake" character varying, "CarModel" character varying, "Kilometer" character varying, "NextService" character varying, "VehicleInTime" timestamp without time zone, "VehicleOutTime" timestamp without time zone, "BatchId" character varying, "DeliveryTerms" character varying, "Certificate" character varying, "IncoTerms" character varying, "Email" character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    -- Get decimal places
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::INT = p_branchid;

    RETURN QUERY
    SELECT 
        A."quotationMasterId",
        A."voucherNo",
        A."quotationNo",
        A."pricingLevelId",
        A."ledgerId",
        A."employeeId",
        A."branchId",
        A."suffixPrefixId",
        A."approved",
        TO_CHAR(A."date", 'DD-Mon-YYYY') AS date,
        ROUND(A."totalAmount"/B."rate", decimalPoint),
        ROUND(A."grandTotal"/B."rate", decimalPoint),
        A."taxType",
        A."narration",
        (CASE 
            WHEN (
                (SELECT COUNT(*) FROM public."tbl_SalesMaster" D WHERE D."quotationMasterId" = A."quotationMasterId") +
                (SELECT COUNT(*) FROM public."tbl_SalesOrderMaster" SO WHERE SO."quotationMasterId" = A."quotationMasterId") +
                (SELECT COUNT(*) FROM public."tbl_DeliveryNoteMaster" DN WHERE DN."quotationMasterId" = A."quotationMasterId")
            ) = 0 THEN 'n' ELSE 'y'
        END)::VARCHAR AS refer,
        ROUND(A."additionalCost"/B."rate", decimalPoint),
        ROUND(A."totalTax"/B."rate", decimalPoint),
        ROUND(A."billDiscount"/B."rate", decimalPoint),
        A."billDiscOnProductStatus",
        A."AddCostonProductStatus",
        A."currencyConversionId",
        A."creditPeriod",
        ROUND(A."roundOff"/B."rate", decimalPoint),
        A."customerName",
        A."CustomerAddress",
        A."CustomerPhone",
        A."costCentreId",
        L."ledgerName",
        C."currencyName",
        CC."CostCentre",
        ROUND(A."othercharge"/B."rate", decimalPoint),
        A."partyRefNo",
        A."partyRefDate",
        A."contactperson",
        A."contactno",
        A."quatationvalidity",
        A."deliveredwithin",
        A."paymentterms",
        A."deliverysite",
        ROUND(A."taxableAmt"/B."rate", decimalPoint),
        E."employeeName",
        E."phoneNo",
        B."currencyId",
        COALESCE(B."rate", 1),
        B."date",
        COALESCE(A."otherChargeLedgerId"::VARCHAR, ''),
        COALESCE(AL."ledgerName", ''),
        A."customerName",
        L."AdditionalNo",
        L."StreetName",
        L."PostboxNo",
        L."CityName",
        L."Country",
        L."BuildingNo",
        L."District",
        COALESCE(A."CarMake", ''),
        COALESCE(A."CarModel", ''),
        COALESCE(A."Kilometer", ''),
        COALESCE(A."NextService", ''),
        COALESCE(A."VehicleInTime", '2000-01-01 00:00:00'),
        COALESCE(A."VehicleOutTime", '2000-01-01 00:00:00'),
        COALESCE(A."BatchId", ''),
        COALESCE(A."DeliveryTerms", ''),
        COALESCE(A."Certificate", ''),
        COALESCE(A."IncoTerms", ''),
        COALESCE(A."Email", '')
    FROM public."tbl_QuotationMaster" A
    INNER JOIN public."tbl_CurrencyConversion" B ON A."currencyConversionId" = B."currencyConversionId"
    INNER JOIN public."tbl_Currency" C ON B."currencyId" = C."currencyId"
    INNER JOIN public."tbl_AccountLedger" L ON A."ledgerId" = L."ledgerId"
    INNER JOIN public."tbl_CostCentre" CC ON A."costCentreId" = CC."costCentreId"
    LEFT JOIN public."tbl_Employee" E ON A."employeeId" = E."employeeId"
    LEFT JOIN public."tbl_AccountLedger" AL ON A."otherChargeLedgerId"::BIGINT = AL."ledgerId"
    WHERE A."quotationMasterId" = p_quotation_master_id;
END;
$$;


ALTER FUNCTION public.get_delivery_note_sales_quotation_master_byid(p_quotation_master_id integer, p_branchid integer) OWNER TO postgres;

--
-- TOC entry 393 (class 1255 OID 27626)
-- Name: get_invoices_for_return(integer, integer, bigint, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_invoices_for_return(p_ledgerid integer, p_branchid integer, p_returnmasterid bigint DEFAULT NULL::bigint, p_isaccountsposting integer DEFAULT 0) RETURNS TABLE(salesmasterid bigint, invoiceno character varying, date timestamp without time zone, qty numeric, freeqty numeric)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT DISTINCT 
        sm."salesMasterId",
        sm."invoiceNo",
        sm."date",
        ((MAX(sd."qty") - COALESCE(SUM(srd."qty"), 0)) + COALESCE(MAX(sr."curQty"), 0)) AS qty,
        ((MAX(sd."freeQty") - COALESCE(SUM(srd."freeQty"), 0)) + COALESCE(MAX(sr."curFreeQty"), 0)) AS freeQty
    FROM "tbl_SalesMaster" sm
    INNER JOIN "tbl_SalesDetails1" sd
        ON sm."salesMasterId" = sd."salesMasterId"
    LEFT JOIN "tbl_SalesReturnMaster" srm
        ON srm."salesMasterId" = sm."salesMasterId"
    LEFT JOIN "tbl_SalesReturnDetails1" srd
        ON srd."ReturnMasterId" = srm."ReturnMasterId"
        AND srd."salesDetails1Id" = sd."salesDetails1Id"
    LEFT JOIN (
        SELECT 
            srd_inner."salesDetails1Id",
            COALESCE(SUM(srd_inner."qty"), 0) AS "curQty",
            COALESCE(SUM(srd_inner."freeQty"), 0) AS "curFreeQty"
        FROM "tbl_SalesReturnDetails1" srd_inner
        WHERE srd_inner."ReturnMasterId" = p_returnMasterId
        GROUP BY srd_inner."salesDetails1Id"
    ) AS sr
        ON sr."salesDetails1Id" = sd."salesDetails1Id"
    WHERE sm."branchId" = p_branchId
      AND (p_ledgerId = 0 OR sm."ledgerId" = p_ledgerId)
      AND (p_isAccountsPosting = 0 OR sm."postedStatus" = 'Yes')
    GROUP BY sd."salesDetails1Id", sm."salesMasterId", sm."invoiceNo", sm."date"
    HAVING ((MAX(sd."qty") - COALESCE(SUM(srd."qty"), 0)) + COALESCE(MAX(sr."curQty"), 0)) > 0
        OR ((MAX(sd."freeQty") - COALESCE(SUM(srd."freeQty"), 0)) + COALESCE(MAX(sr."curFreeQty"), 0)) > 0
    ORDER BY sm."date" DESC, sm."salesMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_invoices_for_return(p_ledgerid integer, p_branchid integer, p_returnmasterid bigint, p_isaccountsposting integer) OWNER TO postgres;

--
-- TOC entry 401 (class 1255 OID 27368)
-- Name: get_product_unit_sales_details(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_product_unit_sales_details(p_productcode character varying) RETURNS TABLE(barcode text, productcode character varying, productname character varying, unitid bigint, unitname character varying, unitconversionid bigint, conversionrate numeric, salesprice numeric)
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        CASE WHEN uc."barcode" = '' THEN p."productCode" ELSE uc."barcode" END AS barcode,
        p."productCode",
        p."productName",
        u."unitId",
        u."UnitName",
        uc."unitConversionId",
        uc."conversionRate",
        s."salesPrice"
    FROM public."tbl_Product" p
    JOIN "tbl_ProductSalesPrice" s ON p."productCode" = s."productCode" 
    JOIN "tbl_Unit" u ON s."unitId" = u."unitId"
    JOIN "tbl_UnitConversion" uc ON uc."unitId" = u."unitId"
                                AND p."productCode" = uc."productCode"
    WHERE uc."productCode" = p_productCode
    ORDER BY p."productCode" ASC;
END;
$$;


ALTER FUNCTION public.get_product_unit_sales_details(p_productcode character varying) OWNER TO postgres;

--
-- TOC entry 351 (class 1255 OID 37615)
-- Name: get_proforma_invoice_details_by_masterid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_proforma_invoice_details_by_masterid(p_proformamasterid bigint) RETURNS TABLE("proformaDetails1Id" bigint, "proformaMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "quotationDetailsId" integer, "ConversionFactor" numeric, barcode text, "productDescription" text, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "taxAmount" numeric, "grossAmount" numeric, "netAmount" numeric, "PurchaseRate" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "SlNo" integer, "Size" character varying, otherchargeonproduct numeric, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        d."proformaDetails1Id",
        d."proformaMasterId",
        d."productCode",
        d.qty,
        d."freeQty",
        d.rate,
        d."unitId",
        d.amount,
        d."quotationDetailsId",
        d."ConversionFactor",
        d.barcode,
        d."productDescription",
        d."discountPercentage",
        d."taxId",
        d."taxType",
        d."taxAmount",
        d."grossAmount",
        d."netAmount",
        d."PurchaseRate",
        d."billDiscOnProduct",
        d."AddCostonProduct",
        d."SlNo",
        d."Size",
        d.otherchargeonproduct,
        d."branchId",
        d."CreatedDate",
        d."CreatedUser",
        d."ModifiedDate",
        d."ModifiedUser"
    FROM public."tbl_proformaInvoiceDetails1" d
    WHERE d."proformaMasterId" = p_proformaMasterId
    ORDER BY d."SlNo" ASC;
END;
$$;


ALTER FUNCTION public.get_proforma_invoice_details_by_masterid(p_proformamasterid bigint) OWNER TO postgres;

--
-- TOC entry 408 (class 1255 OID 37539)
-- Name: get_proforma_invoice_master_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_proforma_invoice_master_byid(p_proformamasterid bigint) RETURNS TABLE("proformaMasterId" bigint, "suffixPrefixId" integer, "voucherNo" character varying, "proformaNo" character varying, date timestamp without time zone, "dueDate" timestamp without time zone, cancelled boolean, "ledgerId" integer, "partyName" character varying, "partyAddress" text, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, status character varying, "taxType" character varying, "quotationMasterId" integer, "AgainstNo" character varying, "pricingLevelId" integer, "employeeId" integer, narration text, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "costCentreId" integer, "BatchId" character varying, "LPONO" character varying, "LPODate" timestamp without time zone, "deliveryDate" timestamp without time zone, "DeliveryTerms" character varying, "PaymentTerms" character varying, "lrNo" character varying, "transportCompany" character varying, "subTotal" numeric, "additionalCost" numeric, "billDiscount" numeric, othercharge numeric, "otherChargeLedgerId" character varying, "taxableAmt" numeric, "totalTax" numeric, "roundOff" numeric, "totalAmount" numeric, "AdvancePerc" numeric, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        pm."proformaMasterId",
        pm."suffixPrefixId",
        pm."voucherNo",
        pm."proformaNo",
        pm.date,
        pm."dueDate",
        pm.cancelled,
        pm."ledgerId",
        pm."partyName",
        pm."partyAddress",
        pm."partyMobile",
        pm."partyVatNo",
        pm."partyRefNo",
        pm."partyRefDate",
        pm.status,
        pm."taxType",
        pm."quotationMasterId",
        pm."AgainstNo",
        pm."pricingLevelId",
        pm."employeeId",
        pm.narration,
        pm."currencyConversionId",
        pm."exchangeRate",
        pm."exchangeDate",
        pm."costCentreId",
        pm."BatchId",
        pm."LPONO",
        pm."LPODate",
        pm."deliveryDate",
        pm."DeliveryTerms",
        pm."PaymentTerms",
        pm."lrNo",
        pm."transportCompany",
        pm."subTotal",
        pm."additionalCost",
        pm."billDiscount",
        pm.othercharge,
        pm."otherChargeLedgerId",
        pm."taxableAmt",
        pm."totalTax",
        pm."roundOff",
        pm."totalAmount",
        pm."AdvancePerc",
        pm."branchId",
        pm."CreatedDate",
        pm."CreatedUser",
        pm."ModifiedDate",
        pm."ModifiedUser"
    FROM public."tbl_proformaInvoiceMaster" pm
    WHERE pm."proformaMasterId" = p_proformaMasterId;
END;
$$;


ALTER FUNCTION public.get_proforma_invoice_master_byid(p_proformamasterid bigint) OWNER TO postgres;

--
-- TOC entry 345 (class 1255 OID 37536)
-- Name: get_proforma_invoice_master_list(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_proforma_invoice_master_list() RETURNS TABLE("proformaMasterId" bigint, "suffixPrefixId" integer, "voucherNo" character varying, "proformaNo" character varying, date timestamp without time zone, "dueDate" timestamp without time zone, cancelled boolean, "ledgerId" integer, "partyName" character varying, "partyAddress" text, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, status character varying, "taxType" character varying, "quotationMasterId" integer, "AgainstNo" character varying, "pricingLevelId" integer, "employeeId" integer, narration text, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "costCentreId" integer, "BatchId" character varying, "LPONO" character varying, "LPODate" timestamp without time zone, "deliveryDate" timestamp without time zone, "DeliveryTerms" character varying, "PaymentTerms" character varying, "lrNo" character varying, "transportCompany" character varying, "subTotal" numeric, "additionalCost" numeric, "billDiscount" numeric, othercharge numeric, "otherChargeLedgerId" character varying, "taxableAmt" numeric, "totalTax" numeric, "roundOff" numeric, "totalAmount" numeric, "AdvancePerc" numeric, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        pm."proformaMasterId",
        pm."suffixPrefixId",
        pm."voucherNo",
        pm."proformaNo",
        pm.date,
        pm."dueDate",
        pm.cancelled,
        pm."ledgerId",
        pm."partyName",
        pm."partyAddress",
        pm."partyMobile",
        pm."partyVatNo",
        pm."partyRefNo",
        pm."partyRefDate",
        pm.status,
        pm."taxType",
        pm."quotationMasterId",
        pm."AgainstNo",
        pm."pricingLevelId",
        pm."employeeId",
        pm.narration,
        pm."currencyConversionId",
        pm."exchangeRate",
        pm."exchangeDate",
        pm."costCentreId",
        pm."BatchId",
        pm."LPONO",
        pm."LPODate",
        pm."deliveryDate",
        pm."DeliveryTerms",
        pm."PaymentTerms",
        pm."lrNo",
        pm."transportCompany",
        pm."subTotal",
        pm."additionalCost",
        pm."billDiscount",
        pm.othercharge,
        pm."otherChargeLedgerId",
        pm."taxableAmt",
        pm."totalTax",
        pm."roundOff",
        pm."totalAmount",
        pm."AdvancePerc",
        pm."branchId",
        pm."CreatedDate",
        pm."CreatedUser",
        pm."ModifiedDate",
        pm."ModifiedUser"
    FROM public."tbl_proformaInvoiceMaster" pm
    ORDER BY pm."proformaMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_proforma_invoice_master_list() OWNER TO postgres;

--
-- TOC entry 302 (class 1255 OID 37327)
-- Name: get_quotation_details_by_quotation_master_id(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_quotation_details_by_quotation_master_id(p_quotationmasterid bigint) RETURNS TABLE("quotationDetailsId" bigint, "quotationMasterId" integer, "productCode" character varying, "unitId" integer, "taxId" integer, qty numeric, "freeQty" numeric, rate numeric, amount numeric, "discountPercentage" numeric, "discountAmount" numeric, "taxAmount" numeric, "grossAmount" numeric, "taxType" character varying, "netAmount" numeric, "ConversionFactor" numeric, barcode character varying, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "SlNo" integer, "PurchaseRate" numeric, otherchargeonproduct numeric, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        qd."quotationDetailsId",
        qd."quotationMasterId",
        qd."productCode",
        qd."unitId",
        qd."taxId",
        qd.qty,
        qd."freeQty",
        qd.rate,
        qd.amount,
        qd."discountPercentage",
        qd."discountAmount",
        qd."taxAmount",
        qd."grossAmount",
        qd."taxType",
        qd."netAmount",
        qd."ConversionFactor",
        qd.barcode,
        qd."billDiscOnProduct",
        qd."AddCostonProduct",
        qd."productDescription",
        qd."SlNo",
        qd."PurchaseRate",
        qd.otherchargeonproduct,
        qd."branchId",
        qd."CreatedDate",
        qd."CreatedUser",
        qd."ModifiedDate",
        qd."ModifiedUser"
    FROM public."tbl_QuotationDetails" qd
    WHERE qd."quotationMasterId" = p_quotationMasterId
    ORDER BY qd."SlNo" ASC;
END;
$$;


ALTER FUNCTION public.get_quotation_details_by_quotation_master_id(p_quotationmasterid bigint) OWNER TO postgres;

--
-- TOC entry 306 (class 1255 OID 37324)
-- Name: get_quotation_master_data_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_quotation_master_data_byid(p_quotationmasterid bigint) RETURNS TABLE("quotationMasterId" bigint, "voucherNo" character varying, "quotationNo" character varying, "suffixPrefixId" integer, "employeeId" integer, "pricingLevelId" integer, date timestamp without time zone, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "BatchId" character varying, "ledgerId" integer, "customerName" character varying, "CustomerAddress" text, "CustomerPhone" character varying, "CustomerVatNo" character varying, "creditPeriod" integer, "costCentreId" integer, narration text, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "taxType" character varying, "totalAmount" numeric, "billDiscount" numeric, "additionalCost" numeric, "otherChargeLedgerId" character varying, othercharge numeric, "taxableAmt" numeric, "totalTax" numeric, "roundOff" numeric, "grandTotal" numeric, "billDiscOnProductStatus" boolean, "AddCostonProductStatus" boolean, approved boolean, contactno character varying, contactperson character varying, deliveredwithin character varying, deliverysite character varying, paymentterms character varying, quatationvalidity character varying, "DeliveryTerms" character varying, "Certificate" character varying, "IncoTerms" character varying, "Email" character varying, "CarMake" character varying, "CarModel" character varying, "Kilometer" character varying, "NextService" character varying, "VehicleInTime" timestamp without time zone, "VehicleOutTime" timestamp without time zone, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, "LedgerName" character varying, "PricingLevelName" character varying, "employeeName" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        qm."quotationMasterId",
        qm."voucherNo",
        qm."quotationNo",
        qm."suffixPrefixId",
        qm."employeeId",
        qm."pricingLevelId",
        qm."date",
        qm."currencyConversionId",
        qm."exchangeRate",
        qm."exchangeDate",
        qm."BatchId",
        qm."ledgerId",
        qm."customerName",
        qm."CustomerAddress",
        qm."CustomerPhone",
        qm."CustomerVatNo",
        qm."creditPeriod",
        qm."costCentreId",
        qm.narration,
        qm."partyRefNo",
        qm."partyRefDate",
        qm."taxType",
        qm."totalAmount",
        qm."billDiscount",
        qm."additionalCost",
        qm."otherChargeLedgerId",
        qm.othercharge,
        qm."taxableAmt",
        qm."totalTax",
        qm."roundOff",
        qm."grandTotal",
        qm."billDiscOnProductStatus",
        qm."AddCostonProductStatus",
        qm.approved,
        qm.contactno,
        qm.contactperson,
        qm.deliveredwithin,
        qm.deliverysite,
        qm.paymentterms,
        qm.quatationvalidity,
        qm."DeliveryTerms",
        qm."Certificate",
        qm."IncoTerms",
        qm."Email",
        qm."CarMake",
        qm."CarModel",
        qm."Kilometer",
        qm."NextService",
        qm."VehicleInTime",
        qm."VehicleOutTime",
        qm."branchId",
        qm."CreatedDate",
        qm."CreatedUser",
        qm."ModifiedDate",
        qm."ModifiedUser",
        l."ledgerName" AS "LedgerName",
        p."PricingLevelName",
        e."employeeName"

    FROM public."tbl_QuotationMaster" qm
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = qm."ledgerId"
    LEFT JOIN public."tbl_PricingLevel" p ON p."PricingLevelId" = qm."pricingLevelId"
    LEFT JOIN public."tbl_Employee" e ON e."employeeId" = qm."employeeId"
    WHERE qm."quotationMasterId" = p_quotationMasterId;
END;
$$;


ALTER FUNCTION public.get_quotation_master_data_byid(p_quotationmasterid bigint) OWNER TO postgres;

--
-- TOC entry 313 (class 1255 OID 37321)
-- Name: get_quotation_master_list(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_quotation_master_list() RETURNS TABLE("quotationMasterId" bigint, "voucherNo" character varying, "quotationNo" character varying, "suffixPrefixId" integer, "employeeId" integer, "pricingLevelId" integer, date timestamp without time zone, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "BatchId" character varying, "ledgerId" integer, "customerName" character varying, "CustomerAddress" text, "CustomerPhone" character varying, "CustomerVatNo" character varying, "creditPeriod" integer, "costCentreId" integer, narration text, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "taxType" character varying, "totalAmount" numeric, "billDiscount" numeric, "additionalCost" numeric, "otherChargeLedgerId" character varying, othercharge numeric, "taxableAmt" numeric, "totalTax" numeric, "roundOff" numeric, "grandTotal" numeric, "billDiscOnProductStatus" boolean, "AddCostonProductStatus" boolean, approved boolean, contactno character varying, contactperson character varying, deliveredwithin character varying, deliverysite character varying, paymentterms character varying, quatationvalidity character varying, "DeliveryTerms" character varying, "Certificate" character varying, "IncoTerms" character varying, "Email" character varying, "CarMake" character varying, "CarModel" character varying, "Kilometer" character varying, "NextService" character varying, "VehicleInTime" timestamp without time zone, "VehicleOutTime" timestamp without time zone, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, "LedgerName" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        qm."quotationMasterId",
        qm."voucherNo",
        qm."quotationNo",
        qm."suffixPrefixId",
        qm."employeeId",
        qm."pricingLevelId",
        qm."date",
        qm."currencyConversionId",
        qm."exchangeRate",
        qm."exchangeDate",
        qm."BatchId",
        qm."ledgerId",
        qm."customerName",
        qm."CustomerAddress",
        qm."CustomerPhone",
        qm."CustomerVatNo",
        qm."creditPeriod",
        qm."costCentreId",
        qm.narration,
        qm."partyRefNo",
        qm."partyRefDate",
        qm."taxType",
        qm."totalAmount",
        qm."billDiscount",
        qm."additionalCost",
        qm."otherChargeLedgerId",
        qm.othercharge,
        qm."taxableAmt",
        qm."totalTax",
        qm."roundOff",
        qm."grandTotal",
        qm."billDiscOnProductStatus",
        qm."AddCostonProductStatus",
        qm.approved,
        qm.contactno,
        qm.contactperson,
        qm.deliveredwithin,
        qm.deliverysite,
        qm.paymentterms,
        qm.quatationvalidity,
        qm."DeliveryTerms",
        qm."Certificate",
        qm."IncoTerms",
        qm."Email",
        qm."CarMake",
        qm."CarModel",
        qm."Kilometer",
        qm."NextService",
        qm."VehicleInTime",
        qm."VehicleOutTime",
        qm."branchId",
        qm."CreatedDate",
        qm."CreatedUser",
        qm."ModifiedDate",
        qm."ModifiedUser",
        l."ledgerName" AS "LedgerName"
    FROM public."tbl_QuotationMaster" qm
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = qm."ledgerId"
    ORDER BY qm."quotationMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_quotation_master_list() OWNER TO postgres;

--
-- TOC entry 353 (class 1255 OID 46000)
-- Name: get_quotation_no_by_date(date, date); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_quotation_no_by_date(fromdate date, todate date) RETURNS TABLE(quotationmasterid bigint, quotationno character varying, "Date" character varying, "CashParty" character varying, totalamount numeric)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        P."quotationMasterId"::BIGINT AS quotationMasterId,
        P."quotationNo",
        TO_CHAR(P."date", 'DD-Mon-YYYY')::VARCHAR AS "Date",  
        L."ledgerName"::VARCHAR AS "CashParty",                
        P."grandTotal" AS totalAmount
    FROM public."tbl_QuotationMaster" AS P
    JOIN public."tbl_AccountLedger" AS L 
        ON P."ledgerId" = L."ledgerId"
    WHERE P."date" BETWEEN fromDate AND toDate AND P."approved" = TRUE
    ORDER BY P."date";
END;
$$;


ALTER FUNCTION public.get_quotation_no_by_date(fromdate date, todate date) OWNER TO postgres;

--
-- TOC entry 323 (class 1255 OID 27512)
-- Name: get_receipt_details_by_master_id(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_receipt_details_by_master_id(p_receiptmasterid integer) RETURNS TABLE(receiptdetailsid bigint, receiptmasterid integer, slno integer, ledgerid integer, amount numeric, narration text, chequeno character varying, chequedate timestamp without time zone, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, createddate timestamp without time zone, createduser character varying, modifieddate timestamp without time zone, modifieduser character varying, branchid integer)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        d."receiptDetailsId",
        d."receiptMasterId",
        d."SlNo",
        d."ledgerId",
        d."amount",               -- ✅ qualified with alias
        d."Narration",
        d."chequeNo",
        d."chequeDate",
        d."currencyConversionId",
        d."exchangeRate",
        d."exchangeDate",
        d."CreatedDate",
        d."CreatedUser",
        d."ModifiedDate",
        d."ModifiedUser",
        d."branchId"
    FROM public."tbl_Receiptdetails" d
    WHERE d."receiptMasterId" = p_receiptMasterId;
END;
$$;


ALTER FUNCTION public.get_receipt_details_by_master_id(p_receiptmasterid integer) OWNER TO postgres;

--
-- TOC entry 329 (class 1255 OID 27510)
-- Name: get_receipt_master_data_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_receipt_master_data_byid(receiptmasterid bigint) RETURNS TABLE("receiptMasterId" bigint, "voucherNo" character varying, "suffixPrefixId" integer, "receiptNo" character varying, date timestamp without time zone, "ledgerId" integer, narration text, "totalAmount" numeric, "userId" character varying, "employeeId" integer, "costCentreId" integer, "ReferenceNo" character varying, "ReferenceDate" timestamp without time zone, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "vanExecutiveId" character varying, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, "LedgerName" character varying, "CostCentre" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        r."receiptMasterId",
        r."voucherNo",
        r."suffixPrefixId",
        r."receiptNo",
        r.date,
        r."ledgerId",
        r.narration,
        r."totalAmount",
        r."userId",
        r."employeeId",
        r."costCentreId",
        r."ReferenceNo",
        r."ReferenceDate",
        r."postedStatus",
        r."postedBy",
        r."postedDate",
        r."vanExecutiveId",
        r."branchId",
        r."CreatedDate",
        r."CreatedUser",
        r."ModifiedDate",
        r."ModifiedUser",
        l."ledgerName" AS LedgerName,
        c."CostCentre" AS CostCentre
    FROM public."tbl_ReceiptMaster" r
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = r."ledgerId"
    LEFT JOIN public."tbl_CostCentre" c ON c."costCentreId" = r."costCentreId"
    WHERE r."receiptMasterId" = receiptMasterId
    ORDER BY r."receiptMasterId";
END;
$$;


ALTER FUNCTION public.get_receipt_master_data_byid(receiptmasterid bigint) OWNER TO postgres;

--
-- TOC entry 376 (class 1255 OID 45681)
-- Name: get_receipt_master_list(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_receipt_master_list() RETURNS TABLE(receiptmasterid bigint, voucherno character varying, suffixprefixid integer, receiptno character varying, date timestamp without time zone, ledgerid integer, narration text, totalamount numeric, userid character varying, employeeid integer, costcentreid integer, referenceno character varying, referencedate timestamp without time zone, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, vanexecutiveid character varying, branchid integer, createddate timestamp without time zone, createduser character varying, modifieddate timestamp without time zone, modifieduser character varying, "LedgerName" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        sr."receiptMasterId",
        sr."voucherNo",
        sr."suffixPrefixId",
        sr."receiptNo",
        sr."date",
        sr."ledgerId",
        sr."narration",
        sr."totalAmount",
        sr."userId",
        sr."employeeId",
        sr."costCentreId",
        sr."ReferenceNo",
        sr."ReferenceDate",
        sr."postedStatus",
        sr."postedBy",
        sr."postedDate",
        sr."vanExecutiveId",
        sr."branchId",
        sr."CreatedDate",
        sr."CreatedUser",
        sr."ModifiedDate",
        sr."ModifiedUser",
        l."ledgerName" AS "LedgerName"
    FROM public."tbl_ReceiptMaster" sr
    LEFT JOIN public."tbl_AccountLedger" l 
        ON l."ledgerId" = sr."ledgerId"
    ORDER BY sr."receiptMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_receipt_master_list() OWNER TO postgres;

--
-- TOC entry 363 (class 1255 OID 27474)
-- Name: get_sales_details1_by_sales_master_id(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_details1_by_sales_master_id(salesmasterid bigint) RETURNS TABLE("salesDetails1Id" bigint, "salesMasterId" integer, "deliveryNoteDetails1Id" character varying, "orderDetails1Id" character varying, "quotationDetailsId" character varying, "proformaDetails1Id" character varying, "SlNo" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "ConversionFactor" numeric, barcode text, "PurchaseRate" numeric, "taxAmount" numeric, "grossAmount" numeric, "netAmount" numeric, amount numeric, "productDescription" text, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, otherchargeonproduct numeric, "salesManId" integer, "GodownId" integer, "RackId" integer, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        sd."salesDetails1Id",
        sd."salesMasterId",
        sd."deliveryNoteDetails1Id",
        sd."orderDetails1Id",
        sd."quotationDetailsId",
        sd."proformaDetails1Id",
        sd."SlNo",
        sd."productCode",
        sd.qty,
        sd."freeQty",
        sd.rate,
        sd."unitId",
        sd."discountPercentage",
        sd."taxId",
        sd."taxType",
        sd."ConversionFactor",
        sd.barcode,
        sd."PurchaseRate",
        sd."taxAmount",
        sd."grossAmount",
        sd."netAmount",
        sd.amount,
        sd."productDescription",
        sd."billDiscOnProduct",
        sd."AddCostonProduct",
        sd.otherchargeonproduct,
        sd."salesManId",
        sd."GodownId",
        sd."RackId",
        sd."branchId",
        sd."CreatedDate",
        sd."CreatedUser",
        sd."ModifiedDate",
        sd."ModifiedUser"
    FROM public."tbl_SalesDetails1" sd
    WHERE sd."salesMasterId" = salesMasterId
    ORDER BY sd."SlNo";
END;
$$;


ALTER FUNCTION public.get_sales_details1_by_sales_master_id(salesmasterid bigint) OWNER TO postgres;

--
-- TOC entry 397 (class 1255 OID 86723)
-- Name: get_sales_invoice_delivery_note_details_with_remaining(integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_delivery_note_details_with_remaining(p_deliverynotemasterid integer, p_salesmasterid integer) RETURNS TABLE("deliveryNoteDetails1Id" bigint, "deliveryNoteMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "discountPercentage" numeric, "taxAmount" numeric, "grossAmount" numeric, "taxType" character varying, "netAmount" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "SlNo" integer, "PurchaseRate" numeric, otherchargeonproduct numeric, "Remark" text, "taxId" integer, "ConversionFactor" numeric, barcode text, category character varying, "partNo" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        A."deliveryNoteDetails1Id",
        A."deliveryNoteMasterId",
        A."productCode",

        --  QTY CALCULATION
        CASE 
            WHEN ROUND(COALESCE(A."qty",0) - COALESCE(SUM(G."qty"),0), 2) > 0 
            THEN ROUND(COALESCE(A."qty",0) - COALESCE(SUM(G."qty"),0), 2)
            ELSE 0
        END
        +
        COALESCE((
            SELECT SD1."qty"
            FROM public."tbl_SalesDetails1" SD1
            WHERE SD1."deliveryNoteDetails1Id"::BIGINT = A."deliveryNoteDetails1Id"
              AND SD1."salesMasterId" = p_salesmasterid
            LIMIT 1
        ),0) AS "qty",

        --  FREE QTY CALCULATION
        CASE 
            WHEN ROUND(COALESCE(A."freeQty",0) - COALESCE(SUM(G."freeQty"),0), 2) > 0 
            THEN ROUND(COALESCE(A."freeQty",0) - COALESCE(SUM(G."freeQty"),0), 2)
            ELSE 0
        END
        +
        COALESCE((
            SELECT SD1."freeQty"
            FROM public."tbl_SalesDetails1" SD1
            WHERE SD1."deliveryNoteDetails1Id"::BIGINT = A."deliveryNoteDetails1Id"
              AND SD1."salesMasterId" = p_salesmasterid
            LIMIT 1
        ),0) AS "freeQty",

        ROUND(A."rate" / D."rate", E."noOfDecimalPlace") AS "rate",
        A."unitId",
        ROUND((A."rate" * A."qty") / D."rate", E."noOfDecimalPlace") AS "amount",
        ROUND(A."discountPercentage" / D."rate", E."noOfDecimalPlace") AS "discountPercentage",
        ROUND(A."taxAmount" / D."rate", E."noOfDecimalPlace") AS "taxAmount",
        ROUND(A."grossAmount" / D."rate", E."noOfDecimalPlace") AS "grossAmount",
        A."taxType",
        ROUND(A."netAmount" / D."rate", E."noOfDecimalPlace") AS "netAmount",
        ROUND(A."billDiscOnProduct" / D."rate", E."noOfDecimalPlace") AS "billDiscOnProduct",
        ROUND(A."AddCostonProduct" / D."rate", E."noOfDecimalPlace") AS "AddCostonProduct",
        A."productDescription",
        A."SlNo",
        ROUND(A."PurchaseRate" / D."rate", E."noOfDecimalPlace") AS "PurchaseRate",
        ROUND(A."otherchargeonproduct" / D."rate", E."noOfDecimalPlace") AS "otherchargeonproduct",
        A."Remark",
        A."taxId",
        A."ConversionFactor",
        A."barcode",
        NULL::VARCHAR AS "category",
        COALESCE(P."partNo"::varchar, '') AS "partNo"

    FROM public."tbl_DeliveryNoteDetails1" A
    INNER JOIN public."tbl_DeliveryNoteMaster" B 
        ON A."deliveryNoteMasterId" = B."deliveryNoteMasterId"
    INNER JOIN public."tbl_CurrencyConversion" D 
        ON B."currencyConversionId" = D."currencyConversionId"
    INNER JOIN public."tbl_Currency" E 
        ON D."currencyId" = E."currencyId"

    LEFT JOIN public."tbl_SalesMaster" H 
        ON H."deliveryNoteMasterId" = B."deliveryNoteMasterId"
    LEFT JOIN public."tbl_SalesDetails1" G 
        ON G."salesMasterId" = H."salesMasterId" 
       AND A."deliveryNoteDetails1Id" = G."deliveryNoteDetails1Id"::BIGINT

    INNER JOIN public."tbl_Product" P 
        ON A."productCode" = P."productCode"

    WHERE A."deliveryNoteMasterId" = p_deliverynotemasterid

    GROUP BY
        A."deliveryNoteDetails1Id", A."deliveryNoteMasterId", A."productCode", 
        A."qty", A."freeQty", A."rate", A."unitId",
        A."discountPercentage", A."taxAmount", A."grossAmount", A."taxType", A."netAmount",
        A."billDiscOnProduct", A."AddCostonProduct", A."productDescription", A."SlNo",
         A."PurchaseRate", A."otherchargeonproduct",
        A."Remark", A."taxId", A."ConversionFactor", A."barcode",
         P."partNo", D."rate", E."noOfDecimalPlace"
    HAVING (SUM(COALESCE(A."qty",0)) > 0 OR SUM(COALESCE(A."freeQty",0)) > 0)
    ORDER BY A."SlNo";
END;
$$;


ALTER FUNCTION public.get_sales_invoice_delivery_note_details_with_remaining(p_deliverynotemasterid integer, p_salesmasterid integer) OWNER TO postgres;

--
-- TOC entry 354 (class 1255 OID 86691)
-- Name: get_sales_invoice_delivery_note_list(integer, integer, integer, boolean); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_delivery_note_list(p_ledgerid integer, p_branchid integer, p_salesmasterid integer, p_isaccountsposting boolean) RETURNS TABLE("deliveryNoteMasterId" bigint, "deliveryNoteNo" character varying, "ledgerName" character varying, "customerName" character varying, "CustomerAddress" character varying, "CustomerPhone" character varying, "DeliveryNoteDate" text, "totalAmount" numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalpoint INTEGER;
BEGIN
    -- Get decimal precision for branch
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = p_branchid;

    RETURN QUERY
    SELECT DISTINCT 
        TEMP."deliveryNoteMasterId",
        TEMP."deliveryNoteNo",
        TEMP."ledgerName",
        TEMP."customerName",
        TEMP."CustomerAddress",
        TEMP."CustomerPhone",
        TO_CHAR(TEMP."DeliveryNoteDate", 'DD-Mon-YYYY') AS "DeliveryNoteDate",
        ROUND(TEMP."totalAmount", decimalpoint)::NUMERIC(24,6) AS "totalAmount",
        TEMP."DeliveryNoteDate"::DATE AS "date"
    FROM (
        SELECT 
            B."deliveryNoteDetails1Id",
            A."deliveryNoteMasterId",
            A."deliveryNoteNo",
            
            -- Remaining qty calculation
            (
                CASE 
                    WHEN (B."qty" - COALESCE(SUM(C."qty"),0) ) > 0 
                    THEN (B."qty" - COALESCE(SUM(C."qty"),0) )
                    ELSE 0 
                END
                +
                CASE 
                    WHEN (
                        COALESCE((
                            SELECT SD1."qty"
                            FROM public."tbl_SalesDetails1" SD1
                            
                            WHERE SD1."deliveryNoteDetails1Id"::BIGINT = B."deliveryNoteDetails1Id"
                              AND SD1."salesMasterId" = p_salesmasterid::INTEGER
                              LIMIT 1
                        ),0) > 0
                    ) THEN COALESCE((
                            SELECT SD1."qty"
                            FROM public."tbl_SalesDetails1" SD1
                            
                            WHERE SD1."deliveryNoteDetails1Id"::BIGINT = B."deliveryNoteDetails1Id"
                              AND SD1."salesMasterId" = p_salesmasterid::INTEGER
                              LIMIT 1
                        ),0)
                    ELSE 0
                END
            ) AS "qty",

            -- Remaining free qty calculation
            (
                CASE 
                    WHEN (B."freeQty" - COALESCE(SUM(C."freeQty"),0) ) > 0 
                    THEN (B."freeQty" - COALESCE(SUM(C."freeQty"),0) )
                    ELSE 0 
                END
                +
                CASE 
                    WHEN (
                        COALESCE((
                            SELECT SD1."freeQty"
                            FROM public."tbl_SalesDetails1" SD1
                            WHERE SD1."deliveryNoteDetails1Id"::BIGINT = B."deliveryNoteDetails1Id"
                              AND SD1."salesMasterId" = p_salesmasterid::INTEGER
                              LIMIT 1
                        ),0) > 0
                    ) THEN COALESCE((
                            SELECT SD1."freeQty"
                            FROM public."tbl_SalesDetails1" SD1
                            WHERE SD1."deliveryNoteDetails1Id"::BIGINT = B."deliveryNoteDetails1Id"
                              AND SD1."salesMasterId" = p_salesmasterid::INTEGER
                              LIMIT 1
                        ),0)
                    ELSE 0
                END
            ) AS "freeQty",

            A."customerName",
            AL."ledgerName",
            A."CustomerAddress",
            A."CustomerPhone",
            A."date"::DATE AS "DeliveryNoteDate",
            A."totalAmount"

        FROM public."tbl_DeliveryNoteMaster" A
        INNER JOIN public."tbl_DeliveryNoteDetails1" B 
            ON A."deliveryNoteMasterId" = B."deliveryNoteMasterId"
        INNER JOIN public."tbl_AccountLedger" AL 
            ON AL."ledgerId" = A."ledgerId"

        LEFT JOIN public."tbl_SalesMaster" D 
            ON D."deliveryNoteMasterId" = A."deliveryNoteMasterId" 
        LEFT JOIN public."tbl_SalesDetails1" C 
            ON C."salesMasterId" = D."salesMasterId" 
           AND B."deliveryNoteDetails1Id" = C."deliveryNoteDetails1Id"::BIGINT

        

        WHERE A."branchId" = p_branchid::INTEGER
          AND ( A."ledgerId" = p_ledgerid::INTEGER)
          AND (
              p_isaccountsposting = FALSE
              OR (A."postedStatus" = 'Yes' AND p_isaccountsposting = TRUE)
          )
        GROUP BY 
            B."deliveryNoteDetails1Id", A."deliveryNoteMasterId", 
            A."deliveryNoteNo", B."qty", B."freeQty", A."customerName", AL."ledgerName", 
            A."CustomerAddress", A."CustomerPhone", A."date", A."totalAmount"
    ) AS TEMP
    WHERE TEMP."qty" > 0 OR TEMP."freeQty" > 0
    ORDER BY TEMP."DeliveryNoteDate" DESC, TEMP."deliveryNoteMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_sales_invoice_delivery_note_list(p_ledgerid integer, p_branchid integer, p_salesmasterid integer, p_isaccountsposting boolean) OWNER TO postgres;

--
-- TOC entry 373 (class 1255 OID 86714)
-- Name: get_sales_invoice_delivery_note_master_byid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_delivery_note_master_byid(p_deliverynotemasterid integer) RETURNS TABLE("deliveryNoteMasterId" bigint, "voucherNo" character varying, "deliveryNoteNo" character varying, date text, "ledgerId" integer, "orderMasterId" integer, "pricingLevelId" integer, "employeeId" integer, narration text, "currencyConversionId" integer, "totalAmount" numeric, "branchId" integer, "suffixPrefixId" integer, "transportCompany" character varying, "currencyId" integer, "noOfDecimalPlace" integer, "Refer" character varying, "quotationMasterId" integer, "taxTotal" numeric, "grandTotal" numeric, "taxType" character varying, "additionalCost" numeric, "totalTax" numeric, "billDiscount" numeric, "roundOff" numeric, "customerName" character varying, "CustomerAddress" character varying, "CustomerPhone" character varying, "RefNo" character varying, "costCentreId" integer, othercharge numeric, "employeeName" character varying, "deliveryLocation" character varying, "driverName" character varying, "contactNo" character varying, "deliveryVehicleNo" character varying, "proformaMasterId" integer, "taxableAmt" numeric, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "LPONo" character varying, "LPODate" timestamp without time zone, "AgainstNo" character varying, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "otherChargeLedgerId" integer, "otherChargeLedgerName" character varying, "BatchId" character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalpoint INTEGER;
BEGIN
    RETURN QUERY
    SELECT 
        A."deliveryNoteMasterId",
        A."voucherNo",
        A."deliveryNoteNo",
        TO_CHAR(A."date", 'DD-Mon-YYYY') AS "date",
        A."ledgerId",
        A."orderMasterId",
        A."pricingLevelId",
        A."employeeId",
        A."narration",
        A."currencyConversionId",
        ROUND((A."totalAmount" / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "totalAmount",
        A."branchId",
        A."suffixPrefixId",
        A."transportCompany",
        B."currencyId",
        C."noOfDecimalPlace",
        CASE 
            WHEN (
                + (SELECT COUNT(*) FROM "tbl_SalesMaster" E WHERE E."deliveryNoteMasterId" = A."deliveryNoteMasterId")) = 0
            THEN 'n' ELSE 'y'
        END::varchar AS "Refer",
        A."quotationMasterId",
        ROUND((A."taxTotal" / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "taxTotal",
        ROUND((A."grandTotal" / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "grandTotal",
        A."taxType",
        ROUND((A."additionalCost" / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "additionalCost",
        ROUND((A."totalTax" / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "totalTax",
        ROUND((A."billDiscount" / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "billDiscount",
        ROUND((A."roundOff" / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "roundOff",
        A."customerName",
        A."CustomerAddress",
        A."CustomerPhone",
        A."RefNo",
        A."costCentreId",
        ROUND((A."othercharge" / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "othercharge",
        E."employeeName",
        A."deliveryLocation",
        A."driverName",
        A."contactNo",
        A."deliveryVehicleNo",
        A."proformaMasterId",
        ROUND((COALESCE(A."taxableAmt", 0) / B."rate")::NUMERIC, C."noOfDecimalPlace") AS "taxableAmt",
        A."postedStatus",
        A."postedBy",
        COALESCE(A."postedDate", '1900-01-01'::timestamp) AS "postedDate",
        COALESCE(A."LPONo", '') AS "LPONo",
        COALESCE(A."LPODate", '1900-01-01'::date) AS "LPODate",
        COALESCE(A."AgainstNo", '') AS "AgainstNo",
        COALESCE(A."exchangeRate", 0) AS "exchangeRate",
        A."exchangeDate",
        COALESCE(A."otherChargeLedgerId", 0) AS "otherChargeLedgerId",
        COALESCE(AL."ledgerName", '') AS "otherChargeLedgerName",
        COALESCE(A."BatchId", '') AS "BatchId"
    FROM public."tbl_DeliveryNoteMaster" A
    INNER JOIN public."tbl_CurrencyConversion" B ON A."currencyConversionId" = B."currencyConversionId"
    INNER JOIN public."tbl_Currency" C ON B."currencyId" = C."currencyId"
    LEFT JOIN public."tbl_Employee" E ON A."employeeId" = E."employeeId"
    LEFT JOIN public."tbl_AccountLedger" AL ON A."otherChargeLedgerId" = AL."ledgerId"
    WHERE A."deliveryNoteMasterId" = p_deliverynotemasterid;

END;
$$;


ALTER FUNCTION public.get_sales_invoice_delivery_note_master_byid(p_deliverynotemasterid integer) OWNER TO postgres;

--
-- TOC entry 372 (class 1255 OID 86665)
-- Name: get_sales_invoice_proforma_details_with_remaining(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_proforma_details_with_remaining(p_proformamasterid integer, p_salesmasterid integer, p_branchid integer) RETURNS TABLE("proformaDetails1Id" bigint, "proformaMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "taxAmount" numeric, "grossAmount" numeric, "netAmount" numeric, amount numeric, "ConversionFactor" numeric, barcode text, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" text, "PurchaseRate" numeric, batch character varying, "SlNo" integer, "Size" character varying, otherchargeonproduct numeric, "productName" character varying, "taxName" character varying, "brandName" character varying, "UnitName" character varying, category character varying, "partNo" character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::Int = p_branchid;

    RETURN QUERY
    SELECT 
        A."proformaDetails1Id",
        A."proformaMasterId",
        A."productCode",

        -- Remaining Qty
        (
            CASE 
                WHEN ((A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0)
                    THEN (A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0))
                ELSE 0 
            END
            +
            CASE 
                WHEN (
                    (SELECT G."proformaMasterId" 
                     FROM "tbl_SalesMaster" G 
                     WHERE G."salesMasterId" = p_salesmasterid)
                    = p_proformamasterid
                )
                THEN COALESCE(
                    (SELECT SD1."qty"
                     FROM "tbl_SalesDetails1" SD1
                     WHERE SD1."proformaDetails1Id"::BIGINT = A."proformaDetails1Id"
                     AND SD1."salesMasterId" = p_salesmasterid), 0)
                ELSE 0 
            END
        ) AS "qty",

        -- Remaining Free Qty
        (
            CASE 
                WHEN ((A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0)
                    THEN (A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0))
                ELSE 0 
            END
            +
            CASE 
                WHEN (
                    (SELECT G."proformaMasterId" 
                     FROM "tbl_SalesMaster" G 
                     WHERE G."salesMasterId" = p_salesmasterid)
                    = p_proformamasterid
                )
                THEN COALESCE(
                    (SELECT SD1."freeQty"
                     FROM "tbl_SalesDetails1" SD1
                     WHERE SD1."proformaDetails1Id"::BIGINT = A."proformaDetails1Id"
                     AND SD1."salesMasterId" = p_salesmasterid), 0)
                ELSE 0 
            END
        ) AS "freeQty",

        ROUND(A."rate" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "rate",
        A."unitId",
        ROUND(A."discountPercentage" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "discountPercentage",
        A."taxId",
        A."taxType",
        ROUND(A."taxAmount" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "taxAmount",
        ROUND(A."grossAmount" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "grossAmount",
        ROUND(A."netAmount" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "netAmount",
        ROUND(A."amount" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "amount",
        A."ConversionFactor",
        A."barcode",
        ROUND(A."billDiscOnProduct" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "billDiscOnProduct",
        ROUND(A."AddCostonProduct" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "AddCostonProduct",
        A."productDescription",
        ROUND(A."PurchaseRate" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "PurchaseRate",
        ''::VARCHAR AS "batch",
        A."SlNo",
        A."Size",
        ROUND(A."otherchargeonproduct" / CC."rate", decimalPoint)::NUMERIC(24,6) AS "otherchargeonproduct",
        P."productName",
        T."rate"::VARCHAR AS "taxName",
        Br."brandName",
        U."UnitName",
        ''::VARCHAR AS "category",
        COALESCE(P."partNo", '') AS "partNo"
    FROM "tbl_proformaInvoiceDetails1" A
    INNER JOIN "tbl_proformaInvoiceMaster" B ON A."proformaMasterId" = B."proformaMasterId"
    INNER JOIN "tbl_CurrencyConversion" CC ON CC."currencyConversionId" = B."currencyConversionId"
    INNER JOIN "tbl_Currency" CU ON CU."currencyId" = CC."currencyId"
    LEFT JOIN "tbl_SalesMaster" G ON G."proformaMasterId" = B."proformaMasterId"
    LEFT JOIN "tbl_SalesDetails1" D ON D."salesMasterId" = G."salesMasterId" AND D."proformaDetails1Id"::BIGINT = A."proformaDetails1Id"
    LEFT JOIN "tbl_SalesOrderMaster" I ON I."proformaMasterId" = B."proformaMasterId"
    LEFT JOIN "tbl_SalesOrderdetails1" J ON J."orderMasterId" = I."orderMasterId" AND J."proformaDetails1Id" = A."proformaDetails1Id"
    INNER JOIN "tbl_Product" P ON A."productCode" = P."productCode"
    INNER JOIN "tbl_Unit" U ON A."unitId" = U."unitId"
    INNER JOIN "tbl_TaxMaster" T ON A."taxId" = T."taxId"
    INNER JOIN "tbl_Brand" Br ON P."brandId" = Br."brandId"

    WHERE A."proformaMasterId" = p_proformamasterid
    GROUP BY 
        A."proformaDetails1Id", A."proformaMasterId", A."productCode", A."unitId",
        A."qty", A."freeQty", A."rate", A."taxId", A."taxType",
        A."discountPercentage", A."billDiscOnProduct", A."otherchargeonproduct", A."ConversionFactor",
        A."taxAmount", A."grossAmount", A."netAmount", A."amount", A."barcode", A."AddCostonProduct",
        A."productDescription", A."PurchaseRate", A."SlNo", A."Size", P."productName",
        T."rate", Br."brandName", U."UnitName", CC."rate", P."partNo"
    HAVING 
        ((A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0
         OR (A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0)
    ORDER BY A."SlNo";

END;
$$;


ALTER FUNCTION public.get_sales_invoice_proforma_details_with_remaining(p_proformamasterid integer, p_salesmasterid integer, p_branchid integer) OWNER TO postgres;

--
-- TOC entry 346 (class 1255 OID 86631)
-- Name: get_sales_invoice_proforma_list(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_proforma_list(p_ledgerid integer, p_branchid integer, p_salesmasterid integer) RETURNS TABLE("proformaMasterId" bigint, "proformaNo" character varying, "partyName" character varying, "partyAddress" text, "partyMobile" character varying, "ProformaDate" timestamp without time zone, "totalAmount" numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    -- Get decimal point from currency setup
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = p_branchid;

    RETURN QUERY
    SELECT DISTINCT
        TEMP."proformaMasterId",
        TEMP."proformaNo",
        TEMP."partyName",
        TEMP."partyAddress",
        TEMP."partyMobile",
        TEMP."ProformaDate"::timestamp AS "ProformaDate",  
        ROUND(TEMP."totalAmount", decimalPoint)::NUMERIC(24,6) AS "totalAmount",
        TEMP."ProformaDate"::DATE AS date
    FROM (
        SELECT 
            B."proformaDetails1Id",
            A."proformaMasterId",
            A."proformaNo",

            -- Remaining Qty
            (
                CASE 
                    WHEN (B."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0
                    THEN (B."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0))
                    ELSE 0 
                END
                +
                CASE 
                    WHEN (
                        COALESCE((SELECT SD1."qty" 
                                  FROM public."tbl_SalesDetails1" SD1 
                                  WHERE SD1."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"
                                  AND SD1."salesMasterId" = p_salesmasterid),0)
                    ) > 0
                    THEN COALESCE(
                        (SELECT SD1."qty"
                         FROM public."tbl_SalesDetails1" SD1
                         WHERE SD1."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"
                         AND SD1."salesMasterId" = p_salesmasterid), 0)
                    ELSE 0 
                END
            ) AS qty,

            -- Remaining FreeQty
            (
                CASE 
                    WHEN (B."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0
                    THEN (B."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0))
                    ELSE 0 
                END
                +
                CASE 
                    WHEN (
                        COALESCE((SELECT SD1."freeQty" 
                                  FROM public."tbl_SalesDetails1" SD1 
                                  WHERE SD1."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"
                                  AND SD1."salesMasterId" = p_salesmasterid),0)
                    ) > 0
                    THEN COALESCE(
                        (SELECT SD1."freeQty"
                         FROM public."tbl_SalesDetails1" SD1
                         WHERE SD1."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"
                         AND SD1."salesMasterId" = p_salesmasterid), 0)
                    ELSE 0 
                END
            ) AS "freeQty",

            A."partyName",
            AL."ledgerName",
            A."partyAddress",
            A."partyMobile",
            A."date" AS "ProformaDate",  
            A."totalAmount"

        FROM public."tbl_proformaInvoiceMaster" A
        INNER JOIN public."tbl_proformaInvoiceDetails1" B ON A."proformaMasterId" = B."proformaMasterId"
        LEFT JOIN public."tbl_DeliveryNoteMaster" E ON E."proformaMasterId" = A."proformaMasterId"
        INNER JOIN public."tbl_AccountLedger" AL ON AL."ledgerId" = A."ledgerId"
        LEFT JOIN public."tbl_SalesMaster" G ON G."proformaMasterId" = A."proformaMasterId"
        LEFT JOIN public."tbl_SalesDetails1" D ON D."salesMasterId" = G."salesMasterId" AND D."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"
        LEFT JOIN public."tbl_SalesOrderMaster" I ON I."proformaMasterId" = A."proformaMasterId"
        LEFT JOIN public."tbl_SalesOrderdetails1" J ON J."orderMasterId" = I."orderMasterId" AND J."proformaDetails1Id" = B."proformaDetails1Id"
        WHERE A."branchId" = p_branchid
          AND (p_ledgerid = 0 OR A."ledgerId" = p_ledgerid)
        GROUP BY 
            B."proformaDetails1Id", A."proformaMasterId", A."proformaNo", 
            B."qty", B."freeQty", A."partyName", AL."ledgerName",
            A."partyAddress", A."partyMobile", A."date", A."totalAmount"
    ) AS TEMP
    WHERE TEMP.qty > 0 OR TEMP."freeQty" > 0
    ORDER BY TEMP."ProformaDate" DESC, TEMP."proformaMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_sales_invoice_proforma_list(p_ledgerid integer, p_branchid integer, p_salesmasterid integer) OWNER TO postgres;

--
-- TOC entry 316 (class 1255 OID 86688)
-- Name: get_sales_invoice_sales_order_details_with_remaining(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_sales_order_details_with_remaining(p_ordermasterid integer, p_salesmasterid integer, p_branchid integer) RETURNS TABLE("orderDetails1Id" bigint, "orderMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "quotationDetailsId" integer, "ConversionFactor" numeric, barcode character varying, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "grossAmount" numeric, "netAmount" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "taxAmount" numeric, "productName" character varying, "taxName" numeric, "brandName" character varying, "unitName" character varying, "SlNo" integer, otherchargeonproduct numeric, category character varying, "partNo" character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INTEGER;
BEGIN
    -- Get decimal precision
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = p_branchid;

    RETURN QUERY
    SELECT 
        A."orderDetails1Id",
        A."orderMasterId",
        A."productCode",

        -- Remaining Qty calculation
        (
            CASE 
                WHEN (A."qty" - COALESCE(SUM(G."qty"),0)) > 0
                THEN (A."qty" - COALESCE(SUM(G."qty"),0))
                ELSE 0
            END
            +
            CASE 
                WHEN (
                    (SELECT COALESCE(SD1."qty",0)
                     FROM "tbl_SalesDetails1" SD1
                     WHERE SD1."orderDetails1Id"::BIGINT = A."orderDetails1Id"
                     AND SD1."salesMasterId" = p_salesmasterid) > 0
                ) THEN (
                    SELECT COALESCE(SD1."qty",0)
                    FROM "tbl_SalesDetails1" SD1
                    WHERE SD1."orderDetails1Id"::BIGINT = A."orderDetails1Id"
                    AND SD1."salesMasterId" = p_salesmasterid
                )
                ELSE 0
            END
        )::NUMERIC(24,6) AS "qty",

        -- Remaining Free Qty calculation
        (
            CASE 
                WHEN (A."freeQty" - COALESCE(SUM(G."freeQty"),0)) > 0
                THEN (A."freeQty" - COALESCE(SUM(G."freeQty"),0))
                ELSE 0
            END
            +
            CASE 
                WHEN (
                    (SELECT COALESCE(SD1."freeQty",0)
                     FROM "tbl_SalesDetails1" SD1
                     WHERE SD1."orderDetails1Id"::BIGINT = A."orderDetails1Id"
                     AND SD1."salesMasterId" = p_salesmasterid) > 0
                ) THEN (
                    SELECT COALESCE(SD1."freeQty",0)
                    FROM "tbl_SalesDetails1" SD1
                    WHERE SD1."orderDetails1Id"::BIGINT = A."orderDetails1Id"
                    AND SD1."salesMasterId" = p_salesmasterid
                )
                ELSE 0
            END
        )::NUMERIC(24,6) AS "freeQty",

        ROUND(A."rate" / C."rate", decimalPoint)::NUMERIC(24,6) AS "rate",
        A."unitId",
        ROUND(A."amount" / C."rate", decimalPoint)::NUMERIC(24,6) AS "amount",
        A."quotationDetailsId",
        A."ConversionFactor",
        A."barcode",
        ROUND(A."discountPercentage" / C."rate", decimalPoint)::NUMERIC(24,6) AS "discountPercentage",
        A."taxId",
        A."taxType",
        ROUND(A."grossAmount" / C."rate", decimalPoint)::NUMERIC(24,6) AS "grossAmount",
        ROUND(A."netAmount" / C."rate", decimalPoint)::NUMERIC(24,6) AS "netAmount",
        ROUND(A."billDiscOnProduct" / C."rate", decimalPoint)::NUMERIC(24,6) AS "billDiscOnProduct",
        ROUND(A."AddCostonProduct" / C."rate", decimalPoint)::NUMERIC(24,6) AS "AddCostonProduct",
        A."productDescription",
        ROUND(A."taxAmount" / C."rate", decimalPoint)::NUMERIC(24,6) AS "taxAmount",
        P."productName",
        T."rate"::NUMERIC(24,6) AS "taxName",
        BR."brandName",
        U."UnitName",
        A."SlNo",
        ROUND(A."otherchargeonproduct" / C."rate", decimalPoint)::NUMERIC(24,6) AS "otherchargeonproduct",
        ''::VARCHAR AS "category",
        COALESCE(P."partNo", '') AS "partNo"

    FROM "tbl_SalesOrderdetails1" A
    INNER JOIN "tbl_SalesOrderMaster" B ON A."orderMasterId" = B."orderMasterId"
    INNER JOIN "tbl_CurrencyConversion" C ON C."currencyConversionId" = B."currencyConversionId"
    INNER JOIN "tbl_Currency" D ON D."currencyId" = C."currencyId"
    INNER JOIN "tbl_Product" P ON A."productCode" = P."productCode"
    INNER JOIN "tbl_Unit" U ON A."unitId" = U."unitId"
    INNER JOIN "tbl_TaxMaster" T ON A."taxId" = T."taxId"
    INNER JOIN "tbl_Brand" BR ON P."brandId" = BR."brandId"
    LEFT JOIN "tbl_SalesMaster" H ON H."orderMasterId" = B."orderMasterId"
    LEFT JOIN "tbl_SalesDetails1" G ON G."salesMasterId" = H."salesMasterId" AND A."orderDetails1Id" = G."orderDetails1Id"::BIGINT

    WHERE A."orderMasterId" = p_ordermasterid

    GROUP BY 
        A."orderDetails1Id", A."orderMasterId", A."productCode", A."unitId", A."qty",
        A."freeQty", A."rate", C."rate", D."noOfDecimalPlace", A."amount",
        A."productDescription", A."quotationDetailsId", A."ConversionFactor", A."barcode",
        A."discountPercentage", A."taxId", A."taxType", A."grossAmount",
        A."netAmount", A."billDiscOnProduct", A."AddCostonProduct", A."taxAmount", P."productName",
        T."rate", BR."brandName", U."UnitName", A."SlNo", A."otherchargeonproduct",
        P."partNo"

    HAVING 
        (CASE 
            WHEN (A."qty" - COALESCE(SUM(G."qty"),0)) > 0 THEN (A."qty" - COALESCE(SUM(G."qty"),0))
            ELSE 0
        END) > 0
        OR
        (CASE 
            WHEN (A."freeQty" - COALESCE(SUM(G."freeQty"),0)) > 0 THEN (A."freeQty" - COALESCE(SUM(G."freeQty"),0))
            ELSE 0
        END) > 0

    ORDER BY A."SlNo";

END;
$$;


ALTER FUNCTION public.get_sales_invoice_sales_order_details_with_remaining(p_ordermasterid integer, p_salesmasterid integer, p_branchid integer) OWNER TO postgres;

--
-- TOC entry 391 (class 1255 OID 86667)
-- Name: get_sales_invoice_sales_order_list(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_sales_order_list(p_ledgerid integer, p_branchid integer, p_salesmasterid integer) RETURNS TABLE("orderMasterId" bigint, "orderNo" character varying, "ledgerName" character varying, "partyName" character varying, "partyAddress" character varying, "partyMobile" character varying, "OrderDate" text, "totalAmount" numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    -- Get decimal precision for the given branch
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = p_branchid;

    RETURN QUERY
    SELECT DISTINCT
        TEMP."orderMasterId",
        TEMP."orderNo",
        TEMP."ledgerName",
        TEMP."partyName",
        TEMP."partyAddress",
        TEMP."partyMobile",
        TO_CHAR(TEMP."OrderDate", 'DD-Mon-YYYY') AS "OrderDate",
        ROUND(TEMP."totalAmount", decimalPoint)::NUMERIC(24,6) AS "totalAmount",
        TEMP."OrderDate"::DATE AS "date"
    FROM (
        SELECT
            B."orderDetails1Id",
            A."orderMasterId",
            A."orderNo",

            -- Remaining Qty
            (
                CASE 
                    WHEN ((B."qty" - COALESCE(SUM(D."qty"),0)) > 0)
                        THEN (B."qty" - COALESCE(SUM(D."qty"),0))
                    ELSE 0 
                END
                +
                CASE 
                    WHEN (
                        (SELECT COALESCE(SD1."qty",0)
                         FROM "tbl_SalesDetails1" SD1
                         WHERE SD1."orderDetails1Id"::BIGINT = B."orderDetails1Id"
                         AND SD1."salesMasterId"::INTEGER = p_salesmasterid) > 0
                    ) THEN (
                        SELECT COALESCE(SD1."qty",0)
                        FROM "tbl_SalesDetails1" SD1
                        WHERE SD1."orderDetails1Id"::BIGINT = B."orderDetails1Id"
                        AND SD1."salesMasterId"::INTEGER = p_salesmasterid
                    )
                    ELSE 0 
                END
            ) AS "qty",

            -- Remaining Free Qty
            (
                CASE 
                    WHEN ((B."freeQty" - COALESCE(SUM(D."freeQty"),0)) > 0)
                        THEN (B."freeQty" - COALESCE(SUM(D."freeQty"),0))
                    ELSE 0 
                END
                +
                CASE 
                    WHEN (
                        (SELECT COALESCE(SD1."freeQty",0)
                         FROM "tbl_SalesDetails1" SD1
                         WHERE SD1."orderDetails1Id"::BIGINT = B."orderDetails1Id"
                         AND SD1."salesMasterId"::INTEGER = p_salesmasterid) > 0
                    ) THEN (
                        SELECT COALESCE(SD1."freeQty",0)
                        FROM "tbl_SalesDetails1" SD1
                        WHERE SD1."orderDetails1Id"::BIGINT = B."orderDetails1Id"
                        AND SD1."salesMasterId"::INTEGER = p_salesmasterid
                    )
                    ELSE 0 
                END
            ) AS "freeQty",

            A."partyName",
            AL."ledgerName",
            A."partyAddress",
            A."partyMobile",
            A."date"::DATE AS "OrderDate",
            A."totalAmount"

        FROM "tbl_SalesOrderMaster" A
        INNER JOIN "tbl_SalesOrderdetails1" B ON A."orderMasterId" = B."orderMasterId"
        INNER JOIN "tbl_AccountLedger" AL ON AL."ledgerId" = A."ledgerId"
        LEFT JOIN "tbl_SalesMaster" G ON G."orderMasterId" = A."orderMasterId" 
        LEFT JOIN "tbl_SalesDetails1" D ON D."salesMasterId" = G."salesMasterId" AND D."orderDetails1Id"::BIGINT = B."orderDetails1Id"

        WHERE A."branchId"::INTEGER = p_branchid
          AND (A."ledgerId"::INTEGER = p_ledgerid)
          AND A."status" = 'Pending'

        GROUP BY 
            B."orderDetails1Id", 
            A."orderMasterId", 
            A."orderNo", 
            B."qty", 
            B."freeQty",
            A."partyName", 
            AL."ledgerName", 
            A."partyAddress", 
            A."partyMobile", 
            A."date", 
            A."totalAmount"
    ) AS TEMP
    WHERE TEMP."qty" > 0 OR TEMP."freeQty" > 0
    ORDER BY TEMP."OrderDate" DESC, TEMP."orderMasterId" DESC;

END;
$$;


ALTER FUNCTION public.get_sales_invoice_sales_order_list(p_ledgerid integer, p_branchid integer, p_salesmasterid integer) OWNER TO postgres;

--
-- TOC entry 375 (class 1255 OID 86628)
-- Name: get_sales_invoice_sales_quotation_details_with_remaining(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_sales_quotation_details_with_remaining(p_quotation_master_id integer, p_salesmasterid integer, p_branchid integer) RETURNS TABLE("quotationDetailsId" bigint, "quotationMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "taxAmount" numeric, "grossAmount" numeric, "netAmount" numeric, amount numeric, "ConversionFactor" numeric, barcode character varying, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "PurchaseRate" numeric, batch character varying, "SlNo" integer, otherchargeonproduct numeric, "productName" character varying, "taxName" numeric, "brandName" character varying, "UnitName" character varying, "partNo" character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    -- Get decimal places from branch’s currency setup
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = p_branchid;

    RETURN QUERY
    SELECT 
        A."quotationDetailsId",
        A."quotationMasterId",
        A."productCode",

        -- Remaining Qty logic (excluding Delivery Note)
        (
            CASE 
                WHEN (A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0
                     THEN (A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0))
                ELSE 0
            END
            +
            CASE 
                WHEN (
                    (SELECT SM."quotationMasterId" FROM public."tbl_SalesMaster" SM WHERE SM."salesMasterId" = p_salesmasterid) = p_quotation_master_id
                )
                THEN COALESCE(
                    (SELECT SD."qty"
                     FROM public."tbl_SalesDetails1" SD
                     WHERE SD."quotationDetailsId"::BIGINT = A."quotationDetailsId"
                     AND SD."salesMasterId" = p_salesmasterid), 0)
                ELSE 0
            END
        ) AS qty,

        (
            CASE 
                WHEN (A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0
                     THEN (A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0))
                ELSE 0
            END
            +
            CASE 
                WHEN (
                    (SELECT SM."quotationMasterId" FROM public."tbl_SalesMaster" SM WHERE SM."salesMasterId" = p_salesmasterid) = p_quotation_master_id
                )
                THEN COALESCE(
                    (SELECT SD."freeQty"
                     FROM public."tbl_SalesDetails1" SD
                     WHERE SD."quotationDetailsId"::BIGINT = A."quotationDetailsId"
                     AND SD."salesMasterId" = p_salesmasterid), 0)
                ELSE 0
            END
        ) AS "freeQty",

        ROUND(A."rate" / CC."rate", decimalPoint)::NUMERIC(24,2) AS rate,
        A."unitId",
        ROUND(A."discountPercentage" / CC."rate", decimalPoint)::NUMERIC(24,2) AS "discountPercentage",
        A."taxId",
        A."taxType",
        ROUND(A."taxAmount" / CC."rate", decimalPoint)::NUMERIC(24,2) AS "taxAmount",
        ROUND(A."grossAmount" / CC."rate", decimalPoint)::NUMERIC(24,2) AS "grossAmount",
        ROUND(A."netAmount" / CC."rate", decimalPoint)::NUMERIC(24,2) AS "netAmount",
        ROUND(A."amount" / CC."rate", decimalPoint)::NUMERIC(24,2) AS amount,

        COALESCE(A."ConversionFactor", 0) AS "ConversionFactor",
        A."barcode",
        ROUND(A."billDiscOnProduct" / CC."rate", decimalPoint)::NUMERIC(24,2) AS "billDiscOnProduct",
        ROUND(A."AddCostonProduct" / CC."rate", decimalPoint)::NUMERIC(24,2) AS "AddCostonProduct",
        A."productDescription",
        ROUND(A."PurchaseRate" / CC."rate", decimalPoint)::NUMERIC(24,2) AS "PurchaseRate",
        ''::varchar AS batch,
        A."SlNo",
        ROUND(A."otherchargeonproduct" / CC."rate", decimalPoint)::NUMERIC(24,2) AS otherchargeonproduct,
        P."productName",
        T."rate"::Numeric AS "taxName",
        Br."brandName",
        U."UnitName",
        COALESCE(P."partNo", '') AS "partNo"
    FROM public."tbl_QuotationDetails" A
    INNER JOIN public."tbl_QuotationMaster" B ON A."quotationMasterId" = B."quotationMasterId"
    INNER JOIN public."tbl_CurrencyConversion" CC ON B."currencyConversionId" = CC."currencyConversionId"
    INNER JOIN public."tbl_Currency" CU ON CU."currencyId" = CC."currencyId"
    INNER JOIN public."tbl_Product" P ON A."productCode" = P."productCode"
    INNER JOIN public."tbl_Unit" U ON A."unitId" = U."unitId"
    INNER JOIN public."tbl_TaxMaster" T ON A."taxId" = T."taxId"
    INNER JOIN public."tbl_Brand" Br ON P."brandId" = Br."brandId"

    -- Sales and SalesOrder only
    LEFT JOIN public."tbl_SalesMaster" G ON G."quotationMasterId" = B."quotationMasterId"
    LEFT JOIN public."tbl_SalesDetails1" D ON D."salesMasterId" = G."salesMasterId" AND D."quotationDetailsId"::BIGINT = A."quotationDetailsId"
    LEFT JOIN public."tbl_SalesOrderMaster" I ON I."quotationMasterId" = B."quotationMasterId"
    LEFT JOIN public."tbl_SalesOrderdetails1" J ON J."orderMasterId" = I."orderMasterId" AND J."quotationDetailsId" = A."quotationDetailsId"

    WHERE A."quotationMasterId" = p_quotation_master_id
    GROUP BY 
        A."quotationDetailsId", A."quotationMasterId", A."productCode", A."unitId", A."qty", A."freeQty",
        A."rate", A."taxId", A."taxType", A."discountPercentage", A."billDiscOnProduct",
        A."otherchargeonproduct", A."ConversionFactor", A."taxAmount", A."grossAmount", A."netAmount", A."amount",
        A."barcode", A."AddCostonProduct", A."productDescription", A."PurchaseRate",
        A."SlNo", P."productName", T."rate", Br."brandName",
        U."UnitName", CC."rate", P."partNo"
    HAVING (
        (A."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0 OR
        (A."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0
    )
    ORDER BY A."SlNo";
END;
$$;


ALTER FUNCTION public.get_sales_invoice_sales_quotation_details_with_remaining(p_quotation_master_id integer, p_salesmasterid integer, p_branchid integer) OWNER TO postgres;

--
-- TOC entry 378 (class 1255 OID 78776)
-- Name: get_sales_invoice_sales_quotation_list(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_invoice_sales_quotation_list(p_ledgerid integer, p_branchid integer, p_salesmasterid integer) RETURNS TABLE("quotationMasterId" bigint, "quotationNo" character varying, "ledgerName" character varying, "customerName" character varying, "CustomerAddress" text, "CustomerPhone" character varying, "QuotationDate" text, "totalAmount" numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    -- Get decimal precision from branch currency
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId"::int = p_branchid;

    RETURN QUERY
    SELECT DISTINCT
        TEMP."quotationMasterId",
        TEMP."quotationNo",
        TEMP."ledgerName",
        TEMP."customerName",
        TEMP."CustomerAddress",
        TEMP."CustomerPhone",
        TO_CHAR(TEMP."QuotationDate", 'DD-Mon-YYYY') AS "QuotationDate",
        ROUND(TEMP."totalAmount", decimalPoint)::NUMERIC(24,6) AS "totalAmount",
        TEMP."QuotationDate"::DATE AS "date"
    FROM (
        SELECT 
            B."quotationDetailsId",
            A."quotationMasterId",
            A."quotationNo",

            -- Qty calculation (sales + salesorder balance)
            (
                CASE 
                    WHEN (B."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0 
                    THEN (B."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0))
                    ELSE 0 
                END
                +
                COALESCE((
                    SELECT SD1."qty"
                    FROM "tbl_SalesDetails1" SD1
                    WHERE SD1."quotationDetailsId"::BIGINT = B."quotationDetailsId"
                      AND SD1."salesMasterId" = p_salesmasterid::BIGINT
                    LIMIT 1
                ), 0)
            ) AS "qty",

            -- FreeQty calculation
            (
                CASE 
                    WHEN (B."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0 
                    THEN (B."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0))
                    ELSE 0 
                END
                +
                COALESCE((
                    SELECT SD2."freeQty"
                    FROM "tbl_SalesDetails1" SD2
                    WHERE SD2."quotationDetailsId"::BIGINT = B."quotationDetailsId"
                      AND SD2."salesMasterId" = p_salesmasterid::BIGINT
                    LIMIT 1
                ), 0)
            ) AS "freeQty",

            A."customerName",
            AL."ledgerName",
            A."CustomerAddress",
            A."CustomerPhone",
            A."date"::DATE AS "QuotationDate",
            A."totalAmount"
        FROM "tbl_QuotationMaster" A
        INNER JOIN "tbl_QuotationDetails" B 
            ON A."quotationMasterId" = B."quotationMasterId" 

        INNER JOIN "tbl_AccountLedger" AL 
            ON AL."ledgerId" = A."ledgerId"
        LEFT JOIN "tbl_SalesMaster" G 
            ON G."quotationMasterId" = A."quotationMasterId" 

        LEFT JOIN "tbl_SalesDetails1" D 
            ON D."salesMasterId" = G."salesMasterId" 
           AND D."quotationDetailsId"::BIGINT = B."quotationDetailsId"
        LEFT JOIN "tbl_SalesOrderMaster" I 
            ON I."quotationMasterId" = A."quotationMasterId" 

        LEFT JOIN "tbl_SalesOrderdetails1" J 
            ON J."orderMasterId" = I."orderMasterId" 
           AND J."quotationDetailsId" = B."quotationDetailsId"
        WHERE A."branchId" = p_branchid::INTEGER
          AND ( A."ledgerId" = p_ledgerid::INTEGER)
          AND A."approved" = 'True'
        GROUP BY 
            B."quotationDetailsId", A."quotationMasterId", A."quotationNo",
            B."qty", B."freeQty", A."customerName", AL."ledgerName",
            A."CustomerAddress", A."CustomerPhone", A."date", A."totalAmount"
    ) AS TEMP
    WHERE TEMP."qty" > 0 OR TEMP."freeQty" > 0
    ORDER BY TEMP."QuotationDate" DESC, TEMP."quotationMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_sales_invoice_sales_quotation_list(p_ledgerid integer, p_branchid integer, p_salesmasterid integer) OWNER TO postgres;

--
-- TOC entry 303 (class 1255 OID 27471)
-- Name: get_sales_master_data_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_master_data_byid(salesmasterid bigint) RETURNS TABLE("salesMasterId" bigint, "voucherNo" character varying, "invoiceNo" character varying, "suffixPrefixId" integer, date timestamp without time zone, "billTime" character varying, "ledgerId" integer, "pricingLevelId" integer, "employeeId" integer, "salesAccount" integer, "currencyConversionId" integer, "taxType" character varying, "GodownId" integer, "costCentreId" integer, "BatchId" integer, "customerName" character varying, "CustomerAddress" text, "CustomerPhone" character varying, "customerVATNo" character varying, "RefNo" character varying, "refDate" timestamp without time zone, "orderRefNo" character varying, "orderRefDate" timestamp without time zone, "creditPeriod" integer, "dueDate" timestamp without time zone, "deliveryDate" timestamp without time zone, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "deliveryNoteMasterId" integer, "orderMasterId" integer, "quotationMasterId" integer, "proformaMasterId" integer, "AgainstNo" character varying, "lrNo" character varying, "transportCompany" character varying, "vehicleNo" character varying, narration text, "taxableAmt" numeric, "subTotal" numeric, "totalTax" numeric, "additionalCost" numeric, "OtherChargeRemark" character varying, "otherChargeLedgerId" integer, othercharge numeric, "billDiscount" numeric, "roundOff" numeric, "totalAmount" numeric, "AdjustmentAmount" numeric, "AdvancePerc" numeric, "RetentionLedgerId" integer, "RetentionType" character varying, "RetentionDueDate" timestamp without time zone, "RetentionPercentage" numeric, "RetentionAmount" numeric, "paymentMode" character varying, "CashLedgerId" integer, "CashRefNo" character varying, "CashAmount" numeric, "BankLedgerId" integer, "BankRefNo" character varying, "BankAmount" numeric, "BillBalanceAmount" numeric, "CarMake" character varying, "CarModel" character varying, "Kilometer" character varying, "NextService" character varying, "VehicleInTime" timestamp without time zone, "VehicleOutTime" timestamp without time zone, "preinvoiceHash" character varying, "currentinvoiceHash" character varying, "UUID" character varying, "Zatcastatus" character varying, logdata character varying, qr_link character varying, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "billDiscOnProductStatus" boolean, "AddCostonProductStatus" boolean, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, "PricingLevelName" character varying, "LedgerName" character varying, "GodownName" character varying, "CostCentre" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        s."salesMasterId" ,
        s."voucherNo" ,
        s."invoiceNo" ,
        s."suffixPrefixId" ,
        s.date ,
        s."billTime" ,
        s."ledgerId" ,
        s."pricingLevelId" ,
        s."employeeId" ,
        s."salesAccount" ,
        s."currencyConversionId" ,
        s."taxType" ,
        s."GodownId" ,
        s."costCentreId" ,
        s."BatchId" ,
        s."customerName" ,
        s."CustomerAddress" text,
        s."CustomerPhone" ,
        s."customerVATNo" ,
        s."RefNo" ,
        s."refDate" ,
        s."orderRefNo" ,
        s."orderRefDate" ,
        s."creditPeriod" ,
        s."dueDate" ,
        s."deliveryDate" ,
        s."exchangeRate" ,
        s."exchangeDate" ,
        s."deliveryNoteMasterId" ,
        s."orderMasterId" ,
        s."quotationMasterId" ,
        s."proformaMasterId" ,
        s."AgainstNo" ,
        s."lrNo" ,
        s."transportCompany" ,
        s."vehicleNo" ,
        s.narration text,
        s."taxableAmt" ,
        s."subTotal" ,
        s."totalTax" ,
        s."additionalCost" ,
        s."OtherChargeRemark" ,
        s."otherChargeLedgerId" ,
        s.othercharge ,
        s."billDiscount" ,
        s."roundOff" ,
        s."totalAmount" ,
        s."AdjustmentAmount" ,
        s."AdvancePerc" ,
        s."RetentionLedgerId" ,
        s."RetentionType" ,
        s."RetentionDueDate" ,
        s."RetentionPercentage" ,
        s."RetentionAmount" ,
        s."paymentMode" ,
        s."CashLedgerId" ,
        s."CashRefNo" ,
        s."CashAmount" ,
        s."BankLedgerId" ,
        s."BankRefNo" ,
        s."BankAmount" ,
        s."BillBalanceAmount" ,
        s."CarMake" ,
        s."CarModel" ,
        s."Kilometer" ,
        s."NextService" ,
        s."VehicleInTime" ,
        s."VehicleOutTime" ,
        s."preinvoiceHash" ,
        s."currentinvoiceHash" ,
        s."UUID" ,
        s."Zatcastatus" ,
        s.logdata ,
        s.qr_link ,
        s."postedStatus" ,
        s."postedBy" ,
        s."postedDate" ,
        s."billDiscOnProductStatus" ,
        s."AddCostonProductStatus" ,
        s."branchId" ,
        s."CreatedDate" ,
        s."CreatedUser" ,
        s."ModifiedDate" ,
        s."ModifiedUser", 
        p."PricingLevelName",
        l."ledgerName",
        g."GodownName",
        c."CostCentre"
    FROM public."tbl_SalesMaster" s
    LEFT JOIN public."tbl_PricingLevel" p ON p."PricingLevelId" = s."pricingLevelId"
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = s."ledgerId"
    LEFT JOIN public."tbl_Godown" g ON g."GodownId" = s."GodownId"
    LEFT JOIN public."tbl_CostCentre" c ON c."costCentreId" = s."costCentreId"
    WHERE s."salesMasterId" = salesMasterId
    ORDER BY s."salesMasterId";
END;
$$;


ALTER FUNCTION public.get_sales_master_data_byid(salesmasterid bigint) OWNER TO postgres;

--
-- TOC entry 331 (class 1255 OID 27507)
-- Name: get_sales_master_list(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_master_list() RETURNS TABLE("salesMasterId" bigint, "voucherNo" character varying, "invoiceNo" character varying, "suffixPrefixId" integer, date timestamp without time zone, "billTime" character varying, "ledgerId" integer, "pricingLevelId" integer, "employeeId" integer, "salesAccount" integer, "currencyConversionId" integer, "taxType" character varying, "GodownId" integer, "costCentreId" integer, "BatchId" integer, "customerName" character varying, "CustomerAddress" text, "CustomerPhone" character varying, "customerVATNo" character varying, "RefNo" character varying, "refDate" timestamp without time zone, "orderRefNo" character varying, "orderRefDate" timestamp without time zone, "creditPeriod" integer, "dueDate" timestamp without time zone, "deliveryDate" timestamp without time zone, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "deliveryNoteMasterId" integer, "orderMasterId" integer, "quotationMasterId" integer, "proformaMasterId" integer, "AgainstNo" character varying, "lrNo" character varying, "transportCompany" character varying, "vehicleNo" character varying, narration text, "taxableAmt" numeric, "subTotal" numeric, "totalTax" numeric, "additionalCost" numeric, "OtherChargeRemark" character varying, "otherChargeLedgerId" integer, othercharge numeric, "billDiscount" numeric, "roundOff" numeric, "totalAmount" numeric, "AdjustmentAmount" numeric, "AdvancePerc" numeric, "RetentionLedgerId" integer, "RetentionType" character varying, "RetentionDueDate" timestamp without time zone, "RetentionPercentage" numeric, "RetentionAmount" numeric, "paymentMode" character varying, "CashLedgerId" integer, "CashRefNo" character varying, "CashAmount" numeric, "BankLedgerId" integer, "BankRefNo" character varying, "BankAmount" numeric, "BillBalanceAmount" numeric, "CarMake" character varying, "CarModel" character varying, "Kilometer" character varying, "NextService" character varying, "VehicleInTime" timestamp without time zone, "VehicleOutTime" timestamp without time zone, "preinvoiceHash" character varying, "currentinvoiceHash" character varying, "UUID" character varying, "Zatcastatus" character varying, logdata character varying, qr_link character varying, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "billDiscOnProductStatus" boolean, "AddCostonProductStatus" boolean, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, "LedgerName" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        s."salesMasterId",
        s."voucherNo",
        s."invoiceNo",
        s."suffixPrefixId",
        s."date",
        s."billTime",
        s."ledgerId",
        s."pricingLevelId",
        s."employeeId",
        s."salesAccount",
        s."currencyConversionId",
        s."taxType",
        s."GodownId",
        s."costCentreId",
        s."BatchId",
        s."customerName",
        s."CustomerAddress",
        s."CustomerPhone",
        s."customerVATNo",
        s."RefNo",
        s."refDate",
        s."orderRefNo",
        s."orderRefDate",
        s."creditPeriod",
        s."dueDate",
        s."deliveryDate",
        s."exchangeRate",
        s."exchangeDate",
        s."deliveryNoteMasterId",
        s."orderMasterId",
        s."quotationMasterId",
        s."proformaMasterId",
        s."AgainstNo",
        s."lrNo",
        s."transportCompany",
        s."vehicleNo",
        s."narration",
        s."taxableAmt",
        s."subTotal",
        s."totalTax",
        s."additionalCost",
        s."OtherChargeRemark",
        s."otherChargeLedgerId",
        s."othercharge",
        s."billDiscount",
        s."roundOff",
        s."totalAmount",
        s."AdjustmentAmount",
        s."AdvancePerc",
        s."RetentionLedgerId",
        s."RetentionType",
        s."RetentionDueDate",
        s."RetentionPercentage",
        s."RetentionAmount",
        s."paymentMode",
        s."CashLedgerId",
        s."CashRefNo",
        s."CashAmount",
        s."BankLedgerId",
        s."BankRefNo",
        s."BankAmount",
        s."BillBalanceAmount",
        s."CarMake",
        s."CarModel",
        s."Kilometer",
        s."NextService",
        s."VehicleInTime",
        s."VehicleOutTime",
        s."preinvoiceHash",
        s."currentinvoiceHash",
        s."UUID",
        s."Zatcastatus",
        s."logdata",
        s."qr_link",
        s."postedStatus",
        s."postedBy",
        s."postedDate",
        s."billDiscOnProductStatus",
        s."AddCostonProductStatus",
        s."branchId",
        s."CreatedDate",
        s."CreatedUser",
        s."ModifiedDate",
        s."ModifiedUser",
        l."ledgerName" AS "LedgerName"
    FROM public."tbl_SalesMaster" s
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = s."ledgerId"
    ORDER BY s."salesMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_sales_master_list() OWNER TO postgres;

--
-- TOC entry 340 (class 1255 OID 54058)
-- Name: get_sales_order_details1_by_ordermaster_id(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_order_details1_by_ordermaster_id(ordermasterid bigint) RETURNS TABLE("orderDetails1Id" bigint, "orderMasterId" integer, "quotationDetailsId" integer, "proformaDetails1Id" integer, "productCode" character varying, "productDescription" character varying, barcode character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, "UnitName" character varying, amount numeric, "ConversionFactor" numeric, "discountPercentage" numeric, "taxId" integer, "taxName" character varying, "taxType" character varying, "taxAmount" numeric, "grossAmount" numeric, "netAmount" numeric, "PurchaseRate" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, otherchargeonproduct numeric, "SlNo" integer, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        D."orderDetails1Id",
        D."orderMasterId",
        D."quotationDetailsId",
        D."proformaDetails1Id",
        D."productCode",
        D."productDescription",
        D."barcode",
        D."qty",
        D."freeQty",
        D."rate",
        D."unitId",
        U."UnitName",
        D."amount",
        D."ConversionFactor",
        D."discountPercentage",
        D."taxId",
        T."taxName",
        D."taxType",
        D."taxAmount",
        D."grossAmount",
        D."netAmount",
        D."PurchaseRate",
        D."billDiscOnProduct",
        D."AddCostonProduct",
        D."otherchargeonproduct",
        D."SlNo",
        D."branchId",
        D."CreatedDate",
        D."CreatedUser",
        D."ModifiedDate",
        D."ModifiedUser"
    FROM public."tbl_SalesOrderdetails1" AS D
    LEFT JOIN public."tbl_Unit" AS U
        ON D."unitId" = U."unitId"
    LEFT JOIN public."tbl_TaxMaster" AS T
        ON D."taxId" = T."taxId"
    WHERE D."orderMasterId" = orderMasterId
    ORDER BY D."SlNo";
END;
$$;


ALTER FUNCTION public.get_sales_order_details1_by_ordermaster_id(ordermasterid bigint) OWNER TO postgres;

--
-- TOC entry 344 (class 1255 OID 54054)
-- Name: get_sales_order_master_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_order_master_byid(p_ordermasterid bigint) RETURNS TABLE("orderMasterId" bigint, "suffixPrefixId" integer, "voucherNo" character varying, "orderNo" character varying, date timestamp without time zone, "dueDate" timestamp without time zone, "ledgerId" integer, "ledgerName" character varying, "partyName" character varying, "partyAddress" character varying, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "pricingLevelId" integer, "PricingLevelName" character varying, "employeeId" integer, "employeeName" character varying, narration text, "costCentreId" integer, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "AgainstNo" character varying, "quotationMasterId" integer, "proformaMasterId" integer, "LPONO" character varying, "LPODate" timestamp without time zone, status character varying, "taxType" character varying, "subTotal" numeric, "additionalCost" numeric, "billDiscount" numeric, othercharge numeric, "taxableAmt" numeric, "totalTax" numeric, "roundOff" numeric, "totalAmount" numeric, "otherChargeLedgerId" character varying, "lrNo" character varying, "transportCompany" character varying, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        M."orderMasterId",
        M."suffixPrefixId",
        M."voucherNo",
        M."orderNo",
        M.date,
        M."dueDate",
        M."ledgerId",
        L."ledgerName",
        M."partyName",
        M."partyAddress",
        M."partyMobile",
        M."partyVatNo",
        M."partyRefNo",
        M."partyRefDate",
        M."pricingLevelId",
        P."PricingLevelName",
        M."employeeId",
        E."employeeName",
        M."narration",
        M."costCentreId",
        M."currencyConversionId",
        M."exchangeRate",
        M."exchangeDate",
        M."AgainstNo",
        M."quotationMasterId",
        M."proformaMasterId",
        M."LPONO",
        M."LPODate",
        M."status",
        M."taxType",
        M."subTotal",
        M."additionalCost",
        M."billDiscount",
        M."othercharge",
        M."taxableAmt",
        M."totalTax",
        M."roundOff",
        M."totalAmount",
        M."otherChargeLedgerId",
        M."lrNo",
        M."transportCompany",
        M."branchId",
        M."CreatedDate",
        M."CreatedUser",
        M."ModifiedDate",
        M."ModifiedUser"
    FROM public."tbl_SalesOrderMaster" AS M
    LEFT JOIN public."tbl_AccountLedger" AS L
        ON M."ledgerId" = L."ledgerId"
    LEFT JOIN public."tbl_Employee" AS E
        ON M."employeeId" = E."employeeId"
    LEFT JOIN public."tbl_PricingLevel" AS P
        ON M."pricingLevelId" = P."PricingLevelId"
    WHERE M."orderMasterId" = p_orderMasterId;
END;
$$;


ALTER FUNCTION public.get_sales_order_master_byid(p_ordermasterid bigint) OWNER TO postgres;

--
-- TOC entry 330 (class 1255 OID 62146)
-- Name: get_sales_order_proforma_details_with_remaining(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_order_proforma_details_with_remaining(in_proforma_master_id integer, in_order_master_id integer, in_branch_id integer) RETURNS TABLE("proformaDetails1Id" bigint, "proformaMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "ConversionFactor" numeric, barcode text, "discountPercentage" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "taxId" integer, "taxType" character varying, "taxAmount" numeric, "PurchaseRate" numeric, batch character varying, "productName" character varying, "taxName" numeric, "brandName" character varying, "UnitName" character varying, "SlNo" integer, "Size" character varying, otherchargeonproduct numeric, "productDescription" text, "partNo" character varying, "netAmount" numeric)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalpoint INTEGER;
BEGIN
    SELECT C."noOfDecimalPlace"
    INTO decimalpoint
    FROM public."tbl_Currency" C
    WHERE C."branchId" = in_branch_id::VARCHAR;

    RETURN QUERY
    SELECT 
        a."proformaDetails1Id",
        a."proformaMasterId",
        a."productCode",
        -- qty
        (
            CASE 
                WHEN (
                    (a."qty" - COALESCE(SUM(d."qty"),0)
                             - COALESCE(SUM(c."qty"),0)
                             - COALESCE(SUM(j."qty"),0)) > 0
                )
                THEN (a."qty" - COALESCE(SUM(d."qty"),0)
                             - COALESCE(SUM(c."qty"),0)
                             - COALESCE(SUM(j."qty"),0))
                ELSE 0
            END
        ) +
        (
            CASE 
                WHEN (
                    (SELECT i."proformaMasterId" 
                     FROM public."tbl_SalesOrderMaster" i
                     WHERE i."orderMasterId" = in_order_master_id)
                    = in_proforma_master_id
                )
                THEN COALESCE((
                    SELECT s."qty"
                    FROM public."tbl_SalesOrderdetails1" s
                    WHERE s."proformaDetails1Id" = a."proformaDetails1Id"
                    AND s."orderMasterId" = in_order_master_id
                ), 0)
                ELSE 0
            END
        ) AS qty,
        -- freeQty
        (
            CASE 
                WHEN (
                    (a."freeQty" - COALESCE(SUM(d."freeQty"),0)
                                 - COALESCE(SUM(c."freeQty"),0)
                                 - COALESCE(SUM(j."freeQty"),0)) > 0
                )
                THEN (a."freeQty" - COALESCE(SUM(d."freeQty"),0)
                                 - COALESCE(SUM(c."freeQty"),0)
                                 - COALESCE(SUM(j."freeQty"),0))
                ELSE 0
            END
        ) +
        (
            CASE 
                WHEN (
                    (SELECT i."proformaMasterId" 
                     FROM public."tbl_SalesOrderMaster" i
                     WHERE i."orderMasterId" = in_order_master_id)
                    = in_proforma_master_id
                )
                THEN COALESCE((
                    SELECT s."freeQty"
                    FROM public."tbl_SalesOrderdetails1" s
                    WHERE s."proformaDetails1Id" = a."proformaDetails1Id"
                    AND s."orderMasterId" = in_order_master_id
                ), 0)
                ELSE 0
            END
        ) AS freeQty,
        ROUND(a."rate"/cc."rate", decimalpoint)::NUMERIC(24,6) AS rate,
        a."unitId",
		a."amount",
        a."ConversionFactor",
        a."barcode",
        ROUND(a."discountPercentage"/cc."rate", decimalpoint)::NUMERIC(24,6),
        ROUND(a."billDiscOnProduct"/cc."rate", decimalpoint)::NUMERIC(24,6),
        ROUND(a."AddCostonProduct"/cc."rate", decimalpoint)::NUMERIC(24,6),
        a."taxId",
        a."taxType",
        a."taxAmount",
        ROUND(a."PurchaseRate"/cc."rate", decimalpoint)::NUMERIC(24,6),
        ''::VARCHAR AS batch,
        p."productName",
        t."rate" AS taxName,
        br."brandName",
        u."UnitName",
        a."SlNo",
        a."Size",
        ROUND(a."otherchargeonproduct"/cc."rate", decimalpoint)::NUMERIC(24,6),
        a."productDescription",
        COALESCE(p."partNo",'') AS partNo,
		a."netAmount"
    FROM public."tbl_proformaInvoiceDetails1" a
    JOIN public."tbl_proformaInvoiceMaster" b ON a."proformaMasterId" = b."proformaMasterId"
    JOIN public."tbl_CurrencyConversion" cc ON b."currencyConversionId" = cc."currencyConversionId"
    JOIN public."tbl_Currency" cu ON cu."currencyId" = cc."currencyId"
    JOIN public."tbl_Product" p ON a."productCode" = p."productCode"
    JOIN public."tbl_Unit" u ON a."unitId" = u."unitId"
    JOIN public."tbl_TaxMaster" t ON a."taxId" = t."taxId"
    JOIN public."tbl_Brand" br ON p."brandId" = br."brandId"
    LEFT JOIN public."tbl_DeliveryNoteMaster" e ON e."proformaMasterId" = b."proformaMasterId"
    LEFT JOIN public."tbl_DeliveryNoteDetails1" c 
        ON c."deliveryNoteMasterId" = e."deliveryNoteMasterId" 
       AND c."proformaDetails1Id"::BIGINT = a."proformaDetails1Id"
    LEFT JOIN public."tbl_SalesMaster" g ON g."proformaMasterId" = b."proformaMasterId"
    LEFT JOIN public."tbl_SalesDetails1" d 
        ON d."salesMasterId" = g."salesMasterId" 
       AND d."proformaDetails1Id"::BIGINT = a."proformaDetails1Id"
    LEFT JOIN public."tbl_SalesOrderMaster" i ON i."proformaMasterId" = b."proformaMasterId" 
    LEFT JOIN public."tbl_SalesOrderdetails1" j 
        ON j."orderMasterId" = i."orderMasterId" 
       AND j."proformaDetails1Id"::BIGINT = a."proformaDetails1Id"
    WHERE a."proformaMasterId" = in_proforma_master_id
    GROUP BY a."proformaDetails1Id", a."proformaMasterId", a."productCode", a."unitId",
             a."qty", a."freeQty", a."rate",  
             a."ConversionFactor", a."barcode", a."discountPercentage",
             a."billDiscOnProduct", a."AddCostonProduct", a."taxId", a."taxType", 
             a."taxAmount", a."productDescription", a."PurchaseRate", p."productName", 
             t."rate", br."brandName", u."UnitName", a."SlNo", a."Size", 
             a."otherchargeonproduct", cc."rate", p."partNo"
    ORDER BY a."SlNo";
END;
$$;


ALTER FUNCTION public.get_sales_order_proforma_details_with_remaining(in_proforma_master_id integer, in_order_master_id integer, in_branch_id integer) OWNER TO postgres;

--
-- TOC entry 322 (class 1255 OID 54279)
-- Name: get_sales_order_proforma_list(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_order_proforma_list(ledgerid integer, branchid integer, in_proformamasterid integer) RETURNS TABLE(proformamasterid bigint, proformano character varying, partyname character varying, partyaddress text, partymobile character varying, proformadate timestamp without time zone, totalamount numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId" = branchId::VARCHAR;

    RETURN QUERY
    SELECT DISTINCT
        TEMP."proformaMasterId",
        TEMP."proformaNo",
        TEMP."partyName",
        TEMP."partyAddress",
        TEMP."partyMobile",
        TEMP."ProformaDate", -- ✅ return timestamp, not text
        ROUND(TEMP."totalAmount", decimalPoint) AS "totalAmount",
        TEMP."ProformaDate"::DATE AS "date"
    FROM (
        SELECT 
            B."proformaDetails1Id",
            A."proformaMasterId",
            A."proformaNo",
            (
                CASE 
                    WHEN ((B."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0)
                    THEN (B."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0))
                    ELSE 0 
                END
            ) +
            (
                CASE 
                    WHEN COALESCE((
                        SELECT SD1."qty"
                        FROM public."tbl_SalesOrderdetails1" SD1
                        WHERE SD1."proformaDetails1Id" = B."proformaDetails1Id"
                        AND A."proformaMasterId" = in_proformaMasterId::BIGINT
                        LIMIT 1
                    ), 0) > 0
                    THEN COALESCE((
                        SELECT SD1."qty"
                        FROM public."tbl_SalesOrderdetails1" SD1
                        WHERE SD1."proformaDetails1Id" = B."proformaDetails1Id"
                        AND A."proformaMasterId" = in_proformaMasterId::BIGINT
                        LIMIT 1
                    ), 0)
                    ELSE 0
                END
            ) AS qty,
            (
                CASE 
                    WHEN ((B."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0)
                    THEN (B."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0))
                    ELSE 0
                END
            ) +
            (
                CASE 
                    WHEN COALESCE((
                        SELECT SD1."freeQty"
                        FROM public."tbl_SalesOrderdetails1" SD1
                        WHERE SD1."proformaDetails1Id" = B."proformaDetails1Id"
                        AND A."proformaMasterId" = in_proformaMasterId::BIGINT
                        LIMIT 1
                    ), 0) > 0
                    THEN COALESCE((
                        SELECT SD1."freeQty"
                        FROM public."tbl_SalesOrderdetails1" SD1
                        WHERE SD1."proformaDetails1Id" = B."proformaDetails1Id"
                        AND A."proformaMasterId" = in_proformaMasterId::BIGINT
                        LIMIT 1
                    ), 0)
                    ELSE 0
                END
            ) AS "freeQty",
            A."partyName",
            AL."ledgerName",
            A."partyAddress",
            A."partyMobile",
            A."date" AS "ProformaDate",
            A."totalAmount"
        FROM public."tbl_proformaInvoiceMaster" A
        INNER JOIN public."tbl_proformaInvoiceDetails1" B 
            ON A."proformaMasterId" = B."proformaMasterId"
        INNER JOIN public."tbl_AccountLedger" AL 
            ON AL."ledgerId" = A."ledgerId"
        LEFT JOIN public."tbl_SalesMaster" G 
            ON G."proformaMasterId" = A."proformaMasterId"
        LEFT JOIN public."tbl_SalesDetails1" D 
            ON D."salesMasterId" = G."salesMasterId"
            AND D."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"
        LEFT JOIN public."tbl_SalesOrderMaster" I 
            ON I."proformaMasterId" = A."proformaMasterId"
        LEFT JOIN public."tbl_SalesOrderdetails1" J 
            ON J."orderMasterId" = I."orderMasterId"
            AND J."proformaDetails1Id"::BIGINT = B."proformaDetails1Id"
        WHERE 
            A."branchId" = branchId
            AND A."ledgerId" = ledgerId::BIGINT
        GROUP BY 
            B."proformaDetails1Id", 
            A."proformaMasterId", 
            A."proformaNo",
            B."qty", 
            B."freeQty",
            A."partyName", 
            AL."ledgerName", 
            A."partyAddress", 
            A."partyMobile", 
            A."date", 
            A."totalAmount"
    ) AS TEMP
    WHERE TEMP.qty > 0 OR TEMP."freeQty" > 0
    ORDER BY "date" DESC, "proformaMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_sales_order_proforma_list(ledgerid integer, branchid integer, in_proformamasterid integer) OWNER TO postgres;

--
-- TOC entry 400 (class 1255 OID 62131)
-- Name: get_sales_order_proforma_master_byid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_order_proforma_master_byid(in_proforma_master_id integer) RETURNS TABLE("proformaMasterId" bigint, "voucherNo" character varying, "proformaNo" character varying, "branchId" integer, "suffixPrefixId" integer, date timestamp without time zone, "dueDate" timestamp without time zone, "ledgerId" integer, narration text, "currencyConversionId" integer, "totalAmount" numeric, "currencyId" integer, "noOfDecimalPlace" integer, "Refer" character, "pricingLevelId" integer, "employeeId" integer, "quotationMasterId" integer, "additionalCost" numeric, "billDiscount" numeric, "taxType" character varying, "lrNo" character varying, "transportCompany" character varying, "costCentreId" integer, "partyName" character varying, "partyAddress" text, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "roundOff" numeric, "ledgerName" character varying, "currencyName" character varying, "CostCentre" character varying, "totalTax" numeric, status character varying, othercharge numeric, "taxableAmt" numeric, "subTotal" numeric, "AgainstNo" character varying, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "otherChargeLedgerId" character varying, "otherChargeLedgerName" character varying, "LPONO" character varying, "LPODate" character varying, "deliveryDate" character varying, "BatchId" character varying, "PaymentTerms" character varying, "DeliveryTerms" character varying, "AdvancePerc" numeric)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_proformaInvoiceMaster" P
    JOIN public."tbl_CurrencyConversion" CC ON P."currencyConversionId" = CC."currencyConversionId"
    JOIN public."tbl_Currency" C ON CC."currencyId" = C."currencyId"
    WHERE P."proformaMasterId" = in_proforma_master_id;

    RETURN QUERY
    SELECT 
        A."proformaMasterId",
        A."voucherNo",
        A."proformaNo",
        A."branchId",
        A."suffixPrefixId",
        A."date",
        A."dueDate",
        A."ledgerId",
        A."narration",
        A."currencyConversionId",
        ROUND((A."totalAmount"/B."rate"), decimalPoint)::NUMERIC(24,6) AS "totalAmount",
        B."currencyId",
        C."noOfDecimalPlace",
        CASE 
            WHEN (
                (SELECT COUNT(*) FROM public."tbl_SalesOrderMaster" SO WHERE SO."proformaMasterId" = A."proformaMasterId") +
                (SELECT COUNT(*) FROM public."tbl_DeliveryNoteMaster" D WHERE D."proformaMasterId" = A."proformaMasterId") +
                (SELECT COUNT(*) FROM public."tbl_SalesMaster" E WHERE E."proformaMasterId" = A."proformaMasterId")
            ) = 0 THEN 'n'
            ELSE 'y'
        END::CHAR(1) AS "Refer",
        A."pricingLevelId",
        A."employeeId",
        A."quotationMasterId",
        ROUND((A."additionalCost"/B."rate"), decimalPoint)::NUMERIC(24,6) AS "additionalCost",
        ROUND((A."billDiscount"/B."rate"), decimalPoint)::NUMERIC(24,6) AS "billDiscount",
        A."taxType",
        A."lrNo",
        A."transportCompany",
        A."costCentreId",
        A."partyName",
        A."partyAddress",
        A."partyMobile",
        A."partyVatNo",
        A."partyRefNo",
        A."partyRefDate",
        ROUND((A."roundOff"/B."rate"), decimalPoint)::NUMERIC(24,6) AS "roundOff",
        L."ledgerName",
        C."currencyName",
        CC."CostCentre",
        ROUND((A."totalTax"/B."rate"), decimalPoint)::NUMERIC(24,6) AS "totalTax",
        A."status",
        ROUND((A."othercharge"/B."rate"), decimalPoint)::NUMERIC(24,6) AS "othercharge",
        ROUND((COALESCE(A."taxableAmt",0)/B."rate"), decimalPoint)::NUMERIC(24,6) AS "taxableAmt",
        ROUND((COALESCE(A."subTotal",0)/B."rate"), decimalPoint)::NUMERIC(24,6) AS "subTotal",
        COALESCE(A."AgainstNo",'') AS "AgainstNo",
        COALESCE(B."rate",0)::NUMERIC(24,6) AS "exchangeRate",
        B."date" AS "exchangeDate",
        COALESCE(A."otherChargeLedgerId",'') AS "otherChargeLedgerId",
        COALESCE(AL."ledgerName",'') AS "otherChargeLedgerName",
        COALESCE(A."LPONO",'') AS "LPONO",
        COALESCE(A."LPODate"::VARCHAR,'') AS "LPODate",
        COALESCE(A."deliveryDate"::VARCHAR,'') AS "deliveryDate",
        COALESCE(A."BatchId",'') AS "BatchId",
        COALESCE(A."PaymentTerms",'') AS "PaymentTerms",
        COALESCE(A."DeliveryTerms",'') AS "DeliveryTerms",
        COALESCE(A."AdvancePerc",0)::NUMERIC(24,6) AS "AdvancePerc"
    FROM public."tbl_proformaInvoiceMaster" A
    JOIN public."tbl_CurrencyConversion" B ON A."currencyConversionId" = B."currencyConversionId"
    JOIN public."tbl_Currency" C ON B."currencyId" = C."currencyId"
    JOIN public."tbl_AccountLedger" L ON A."ledgerId" = L."ledgerId"
    JOIN public."tbl_CostCentre" CC ON A."costCentreId" = CC."costCentreId"
    LEFT JOIN public."tbl_AccountLedger" AL ON A."otherChargeLedgerId" = AL."ledgerId"::VARCHAR
    WHERE A."proformaMasterId" = in_proforma_master_id;
END;
$$;


ALTER FUNCTION public.get_sales_order_proforma_master_byid(in_proforma_master_id integer) OWNER TO postgres;

--
-- TOC entry 404 (class 1255 OID 62014)
-- Name: get_sales_order_quotation_details_with_remaining(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_order_quotation_details_with_remaining(in_quotationmasterid integer, in_ordermasterid integer, in_branchid integer) RETURNS TABLE("quotationDetailsId" bigint, "quotationMasterId" integer, "productCode" character varying, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "ConversionFactor" numeric, barcode character varying, "discountPercentage" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "taxId" integer, "taxType" character varying, "taxAmount" numeric, "PurchaseRate" numeric, "productName" character varying, "taxName" numeric, "brandName" character varying, "UnitName" character varying, "SlNo" integer, otherchargeonproduct numeric, "productDescription" character varying, "partNo" character varying, "grossAmount" numeric, "netAmount" numeric)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_decimalPoint INT;
BEGIN
    -- Get decimal precision for branch
    SELECT C."noOfDecimalPlace"
    INTO v_decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId" = In_branchId::VARCHAR;

    RETURN QUERY
    SELECT 
        A."quotationDetailsId",
        A."quotationMasterId",
        A."productCode",
        
        -- Remaining quantity calculation

        GREATEST(A."qty" - COALESCE(SUM(SD."qty"), 0) - COALESCE(SUM(J."qty"), 0) - COALESCE(SUM(C."qty"), 0), 0)::NUMERIC(24,6) AS "qty",
    	GREATEST(A."freeQty" - COALESCE(SUM(SD."freeQty"), 0) - COALESCE(SUM(J."freeQty"), 0) - COALESCE(SUM(C."freeQty"), 0) , 0)::NUMERIC(24,6) AS "freeQty",
    
        ROUND(A."rate"/COALESCE(CC."rate",1), v_decimalPoint)::NUMERIC(24,6) AS "rate",
        A."unitId",
        ROUND(A."qty" * A."rate", v_decimalPoint)::NUMERIC(24,6) AS "amount",
        A."ConversionFactor",
        A."barcode",
        A."discountPercentage",
        A."billDiscOnProduct",
        A."AddCostonProduct",
        A."taxId",
        A."taxType",
        A."taxAmount",
        A."PurchaseRate",
        P."productName",
        T."rate" AS "taxName",
        B."brandName",
        U."UnitName",
        A."SlNo",
        A."otherchargeonproduct",
        A."productDescription",
        COALESCE(P."partNo",'') AS "partNo",
        A."grossAmount",
        A."netAmount"
    FROM public."tbl_QuotationDetails" A
    JOIN public."tbl_QuotationMaster" QM 
        ON A."quotationMasterId" = QM."quotationMasterId"
    JOIN public."tbl_CurrencyConversion" CC 
        ON QM."currencyConversionId" = CC."currencyConversionId"
    JOIN public."tbl_Product" P 
        ON A."productCode" = P."productCode"
    JOIN public."tbl_Unit" U 
        ON A."unitId" = U."unitId"
    JOIN public."tbl_TaxMaster" T 
        ON A."taxId" = T."taxId"
    JOIN public."tbl_Brand" B 
        ON P."brandId" = B."brandId"
    LEFT JOIN public."tbl_SalesMaster" SM 
        ON SM."quotationMasterId" = QM."quotationMasterId"
    LEFT JOIN public."tbl_SalesDetails1" SD 
        ON SD."salesMasterId" = SM."salesMasterId"
        AND SD."quotationDetailsId"::BIGINT = A."quotationDetailsId"
		LEFT JOIN public."tbl_SalesOrderMaster" AS I ON
                       I."quotationMasterId" = QM."quotationMasterId"
                                             
LEFT JOIN public."tbl_SalesOrderdetails1" AS J ON
                        J."orderMasterId"=I."orderMasterId"  AND
                        J."quotationDetailsId"=  A."quotationDetailsId"
LEFT JOIN public."tbl_DeliveryNoteMaster" AS E ON
                      E."quotationMasterId" = QM."quotationMasterId"
                        
LEFT JOIN public."tbl_DeliveryNoteDetails1" AS C ON
                       C."deliveryNoteMasterId"=E."deliveryNoteMasterId"  AND
                       C."quotationDetailsId" = A."quotationDetailsId"
    WHERE A."quotationMasterId" = In_quotationmasterId
    GROUP BY 
        A."quotationDetailsId", A."quotationMasterId", A."productCode", 
        A."unitId", A."qty", A."freeQty", A."rate", A."ConversionFactor", 
        A."barcode", A."discountPercentage", A."billDiscOnProduct", 
        A."AddCostonProduct", A."taxId", A."taxType", A."taxAmount", 
        A."productDescription", A."PurchaseRate", P."productName", 
        T."rate", B."brandName", U."UnitName", A."SlNo",  
        A."otherchargeonproduct", CC."rate", P."partNo", 
        A."grossAmount", A."netAmount"
    ORDER BY A."SlNo";
END;
$$;


ALTER FUNCTION public.get_sales_order_quotation_details_with_remaining(in_quotationmasterid integer, in_ordermasterid integer, in_branchid integer) OWNER TO postgres;

--
-- TOC entry 324 (class 1255 OID 54066)
-- Name: get_sales_order_quotation_list(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_order_quotation_list(ledgerid integer, branchid integer, ordermasterid integer) RETURNS TABLE(quotationmasterid bigint, quotationno character varying, ledgername character varying, customername character varying, customeraddress character varying, customerphone character varying, quotationdate character varying, totalamount numeric, date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    decimalPoint INT;
BEGIN
    -- Get decimal places
    SELECT C."noOfDecimalPlace"
    INTO decimalPoint
    FROM public."tbl_Currency" C
    WHERE C."branchId" = branchId::VARCHAR;

    RETURN QUERY
    SELECT DISTINCT
        TEMP."quotationMasterId",
        TEMP."quotationNo"::VARCHAR,
        TEMP."ledgerName"::VARCHAR,
        TEMP."customerName"::VARCHAR,
        TEMP."CustomerAddress"::VARCHAR,
        TEMP."CustomerPhone"::VARCHAR,
        TO_CHAR(TEMP."QuotationDate", 'DD-Mon-YYYY')::VARCHAR AS "quotationDate",
        ROUND(TEMP."totalAmount"::NUMERIC, decimalPoint) AS "totalAmount",
        TEMP."QuotationDate"::DATE AS "date"
    FROM (
        SELECT 
            B."quotationDetailsId",
            A."quotationMasterId",
            A."quotationNo",
            
            -- Calculate available qty
            CASE 
                WHEN ( (B."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0)) > 0 )
                THEN (B."qty" - COALESCE(SUM(D."qty"),0) - COALESCE(SUM(J."qty"),0))
                ELSE 0 
            END
            +
            (
                CASE 
                    WHEN (
                        COALESCE(
                            (SELECT SD1.qty FROM "tbl_SalesOrderdetails1" AS SD1 
                             WHERE SD1."quotationDetailsId" = B."quotationDetailsId" 
                             AND SD1."orderMasterId" = orderMasterId), 0
                        ) > 0
                    ) 
                    THEN COALESCE(
                        (SELECT SD1.qty FROM "tbl_SalesOrderdetails1" AS SD1 
                         WHERE SD1."quotationDetailsId" = B."quotationDetailsId" 
                         AND SD1."orderMasterId" = orderMasterId), 0
                    )
                    ELSE 0
                END
            ) AS qty,
            
            CASE 
                WHEN ( (B."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0)) > 0 )
                THEN (B."freeQty" - COALESCE(SUM(D."freeQty"),0) - COALESCE(SUM(J."freeQty"),0) )
                ELSE 0 
            END
            +
            (
                CASE 
                    WHEN (
                        COALESCE(
                            (SELECT SD1."freeQty" FROM "tbl_SalesOrderdetails1" AS SD1 
                             WHERE SD1."quotationDetailsId" = B."quotationDetailsId" 
                             AND SD1."orderMasterId" = orderMasterId), 0
                        ) > 0
                    )
                    THEN COALESCE(
                        (SELECT SD1."freeQty" FROM "tbl_SalesOrderdetails1" AS SD1 
                         WHERE SD1."quotationDetailsId" = B."quotationDetailsId" 
                         AND SD1."orderMasterId" = orderMasterId), 0
                    )
                    ELSE 0
                END
            ) AS "freeQty",

            A."customerName",
            AL."ledgerName",
            A."CustomerAddress",
            A."CustomerPhone",
            A."date" AS "QuotationDate",
            A."grandTotal" AS "totalAmount"
        FROM public."tbl_QuotationMaster" A
        INNER JOIN public."tbl_QuotationDetails" B ON A."quotationMasterId" = B."quotationMasterId"
        INNER JOIN public."tbl_AccountLedger" AL ON AL."ledgerId" = A."ledgerId"

        LEFT JOIN public."tbl_SalesMaster" G ON G."quotationMasterId" = A."quotationMasterId"
        LEFT JOIN public."tbl_SalesDetails1" D ON D."salesMasterId" = G."salesMasterId"
            AND D."quotationDetailsId"::BIGINT = B."quotationDetailsId"

        LEFT JOIN public."tbl_SalesOrderMaster" I ON I."quotationMasterId" = A."quotationMasterId"
        LEFT JOIN public."tbl_SalesOrderdetails1" J ON J."orderMasterId" = I."orderMasterId"
            AND J."quotationDetailsId"::BIGINT = B."quotationDetailsId"

        WHERE A."branchId" = branchId
          AND A."ledgerId" = ledgerId
          AND A."approved" = TRUE
        GROUP BY 
            B."quotationDetailsId", 
            A."quotationMasterId", 
            A."quotationNo",
            B.qty, 
            B."freeQty", 
            A."customerName", 
            AL."ledgerName",
            A."CustomerAddress", 
            A."CustomerPhone", 
            A."date", 
            A."grandTotal"
    ) AS TEMP
    WHERE qty > 0 OR "freeQty" > 0
    ORDER BY "date" DESC, "quotationMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_sales_order_quotation_list(ledgerid integer, branchid integer, ordermasterid integer) OWNER TO postgres;

--
-- TOC entry 305 (class 1255 OID 54438)
-- Name: get_sales_order_quotation_master_byid(integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_order_quotation_master_byid(in_quotationmasterid integer, in_branchid integer) RETURNS TABLE("quotationMasterId" bigint, "voucherNo" character varying, "quotationNo" character varying, "pricingLevelId" integer, "ledgerId" integer, "employeeId" integer, "branchId" integer, "suffixPrefixId" integer, approved boolean, date character varying, "totalAmount" numeric, "grandTotal" numeric, "taxType" character varying, narration text, refer character, "additionalCost" numeric, "totalTax" numeric, "billDiscount" numeric, "billDiscOnProductStatus" boolean, "AddCostonProductStatus" boolean, "currencyConversionId" integer, "creditPeriod" integer, "roundOff" numeric, "customerName" character varying, "CustomerAddress" text, "CustomerPhone" character varying, "costCentreId" integer, "ledgerName" character varying, "currencyName" character varying, "CostCentre" character varying, othercharge numeric, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, contactperson character varying, contactno character varying, quatationvalidity character varying, deliveredwithin character varying, paymentterms character varying, deliverysite character varying, "taxableAmt" numeric, salesman character varying, "salesmanPhone" character varying, "currencyId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "otherChargeLedgerId" character varying, "otherChargeLedgerName" character varying, "partyName" character varying, "partyAdditionalNo" character varying, "partyStreetName" character varying, "partyPostboxNo" character varying, "partyCityName" character varying, "partyCountry" character varying, "partyBuildingNo" character varying, "partyDistrict" character varying, "CarMake" character varying, "CarModel" character varying, "Kilometer" character varying, "NextService" character varying, "VehicleInTime" timestamp without time zone, "VehicleOutTime" timestamp without time zone, "BatchId" character varying, "DeliveryTerms" character varying, "Certificate" character varying, "IncoTerms" character varying, "Email" character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_decimal_point INT;
BEGIN
    -- Get decimal precision for branch currency
    SELECT c."noOfDecimalPlace"
    INTO v_decimal_point
    FROM public."tbl_Currency" c
    WHERE c."branchId" = In_branchId::VARCHAR;

    RETURN QUERY
    SELECT 
        a."quotationMasterId",
        a."voucherNo",
        a."quotationNo",
        a."pricingLevelId",
        a."ledgerId",
        a."employeeId",
        a."branchId",
        a."suffixPrefixId",
        a."approved",
        TO_CHAR(a."date", 'DD-Mon-YYYY')::VARCHAR AS "date",
        ROUND(a."totalAmount"/COALESCE(b."rate",1), v_decimal_point) AS "totalAmount",
        ROUND(a."grandTotal"/COALESCE(b."rate",1), v_decimal_point) AS "grandTotal",
        a."taxType",
        a."narration",
        CASE 
            WHEN (
                (SELECT COUNT(*) FROM public."tbl_SalesMaster" d WHERE d."quotationMasterId" = a."quotationMasterId") +
                (SELECT COUNT(*) FROM public."tbl_SalesOrderMaster" so WHERE so."quotationMasterId" = a."quotationMasterId")
            ) = 0 THEN 'n' ELSE 'y'
        END::CHAR(1) AS "refer",
        ROUND(a."additionalCost"/COALESCE(b."rate",1), v_decimal_point) AS "additionalCost",
        ROUND(a."totalTax"/COALESCE(b."rate",1), v_decimal_point) AS "totalTax",
        ROUND(a."billDiscount"/COALESCE(b."rate",1), v_decimal_point) AS "billDiscount",
        a."billDiscOnProductStatus",
        a."AddCostonProductStatus",
        a."currencyConversionId",
        a."creditPeriod",
        ROUND(a."roundOff"/COALESCE(b."rate",1), v_decimal_point) AS "roundOff",
        a."customerName",
        a."CustomerAddress",
        a."CustomerPhone",
        a."costCentreId",
        l."ledgerName",
        c."currencyName",
        cc."CostCentre",
        ROUND(a."othercharge"/COALESCE(b."rate",1), v_decimal_point) AS "othercharge",
        a."partyRefNo",
        a."partyRefDate",
        a."contactperson",
        a."contactno",
        a."quatationvalidity",
        a."deliveredwithin",
        a."paymentterms",
        a."deliverysite",
        ROUND(a."taxableAmt"/COALESCE(b."rate",1), v_decimal_point) AS "taxableAmt",
        COALESCE(e."employeeName",'') AS "salesman",
        COALESCE(e."phoneNo",'') AS "salesmanPhone",
        b."currencyId",
        COALESCE(b."rate",1) AS "exchangeRate",
        b."date" AS "exchangeDate",
        a."otherChargeLedgerId",
        COALESCE(al."ledgerName",'') AS "otherChargeLedgerName",
        a."customerName" AS "partyName",
        l."AdditionalNo" AS "partyAdditionalNo",
        l."StreetName" AS "partyStreetName",
        l."PostboxNo" AS "partyPostboxNo",
        l."CityName" AS "partyCityName",
        l."Country" AS "partyCountry",
        l."BuildingNo" AS "partyBuildingNo",
        l."District" AS "partyDistrict",
        a."CarMake",
        a."CarModel",
        a."Kilometer",
        a."NextService",
        COALESCE(a."VehicleInTime",'2000-01-01 00:00:00') AS "VehicleInTime",
        COALESCE(a."VehicleOutTime",'2000-01-01 00:00:00') AS "VehicleOutTime",
        COALESCE(a."BatchId",'') AS "BatchId",
        COALESCE(a."DeliveryTerms",'') AS "DeliveryTerms",
        COALESCE(a."Certificate",'') AS "Certificate",
        COALESCE(a."IncoTerms",'') AS "IncoTerms",
        COALESCE(a."Email",'') AS "Email"
    FROM public."tbl_QuotationMaster" a
    JOIN public."tbl_CurrencyConversion" b ON a."currencyConversionId" = b."currencyConversionId"
    JOIN public."tbl_Currency" c ON b."currencyId" = c."currencyId"
    JOIN public."tbl_AccountLedger" l ON a."ledgerId"::BIGINT = l."ledgerId"
    JOIN public."tbl_CostCentre" cc ON a."costCentreId" = cc."costCentreId"
    LEFT JOIN public."tbl_Employee" e ON a."employeeId" = e."employeeId"
    LEFT JOIN public."tbl_AccountLedger" al ON a."otherChargeLedgerId" = al."ledgerId"::VARCHAR
    WHERE a."quotationMasterId" = In_quotationmasterId::BIGINT;
END;
$$;


ALTER FUNCTION public.get_sales_order_quotation_master_byid(in_quotationmasterid integer, in_branchid integer) OWNER TO postgres;

--
-- TOC entry 382 (class 1255 OID 27605)
-- Name: get_sales_return_details1_by_return_master_id(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_return_details1_by_return_master_id(returnmasterid bigint) RETURNS TABLE("ReturnDetails1Id" bigint, "ReturnMasterId" integer, "salesDetails1Id" integer, "productCode" character varying, "productDescription" text, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, "discountPercentage" numeric, "taxId" integer, "taxType" character varying, "taxAmount" numeric, "grossAmount" numeric, "netAmount" numeric, amount numeric, "ConversionFactor" numeric, barcode character varying, "PurchaseRate" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "GodownId" integer, "RackId" character varying, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        sd."ReturnDetails1Id",
        sd."ReturnMasterId",
        sd."salesDetails1Id",
        sd."productCode",
        sd."productDescription",
        sd.qty,
        sd."freeQty",
        sd.rate,
        sd."unitId",
        sd."discountPercentage",
        sd."taxId",
        sd."taxType",
        sd."taxAmount",
        sd."grossAmount",
        sd."netAmount",
        sd.amount,
        sd."ConversionFactor",
        sd.barcode,
        sd."PurchaseRate",
        sd."billDiscOnProduct",
        sd."AddCostonProduct",
        sd."GodownId",
        sd."RackId",
        sd."branchId",
        sd."CreatedDate",
        sd."CreatedUser",
        sd."ModifiedDate",
        sd."ModifiedUser"
    FROM public."tbl_SalesReturnDetails1" sd
    WHERE sd."ReturnMasterId" = ReturnMasterId
    ORDER BY sd."ReturnDetails1Id";
END;
$$;


ALTER FUNCTION public.get_sales_return_details1_by_return_master_id(returnmasterid bigint) OWNER TO postgres;

--
-- TOC entry 315 (class 1255 OID 45645)
-- Name: get_sales_return_master_data_byid(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_return_master_data_byid(returnmasterid bigint) RETURNS TABLE("ReturnMasterId" bigint, "voucherNo" character varying, "suffixPrefixId" integer, "returnNo" character varying, date timestamp without time zone, "billTime" character varying, "salesMasterId" integer, "ledgerId" integer, "salesAccountId" integer, "pricingLevelId" integer, "employeeId" integer, "costCentreId" integer, "BatchId" character varying, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "partyName" character varying, "partyAddress" text, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "lrNo" character varying, "transportCompany" character varying, narration text, "taxType" character varying, "taxableAmt" numeric, "subTotal" numeric, "totalTax" numeric, "additionalCost" numeric, "billDiscount" numeric, "roundOff" numeric, "totalAmount" numeric, "paymentMode" character varying, "CashLedgerId" integer, "CashRefNo" character varying, "CashAmount" numeric, "BankLedgerId" integer, "BankRefNo" character varying, "BankAmount" numeric, "BillBalanceAmount" numeric, "billDiscOnProductStatus" boolean, "AddCostonProductStatus" boolean, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "AgainstNo" character varying, "isVan" boolean, "vanExecutiveId" character varying, "preinvoiceHash" character varying, "currentinvoiceHash" character varying, "UUID" character varying, status character varying, logdata character varying, qr_link character varying, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, reason text, "GodownId" integer, "creditPeriod" integer, "PricingLevelName" character varying, "LedgerName" character varying, "CostCentre" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        sr."ReturnMasterId",
        sr."voucherNo",
        sr."suffixPrefixId",
        sr."returnNo",
        sr.date,
        sr."billTime",
        sr."salesMasterId",
        sr."ledgerId",
        sr."salesAccountId",
        sr."pricingLevelId",
        sr."employeeId",
        sr."costCentreId",
        sr."BatchId",
        sr."currencyConversionId",
        sr."exchangeRate",
        sr."exchangeDate",
        sr."partyName",
        sr."partyAddress",
        sr."partyMobile",
        sr."partyVatNo",
        sr."partyRefNo",
        sr."partyRefDate",
        sr."lrNo",
        sr."transportCompany",
        sr.narration,
        sr."taxType",
        sr."taxableAmt",
        sr."subTotal",
        sr."totalTax",
        sr."additionalCost",
        sr."billDiscount",
        sr."roundOff",
        sr."totalAmount",
        sr."paymentMode",
        sr."CashLedgerId",
        sr."CashRefNo",
        sr."CashAmount",
        sr."BankLedgerId",
        sr."BankRefNo",
        sr."BankAmount",
        sr."BillBalanceAmount",
        sr."billDiscOnProductStatus",
        sr."AddCostonProductStatus",
        sr."postedStatus",
        sr."postedBy",
        sr."postedDate",
        sr."AgainstNo",
        sr."isVan",
        sr."vanExecutiveId",
        sr."preinvoiceHash",
        sr."currentinvoiceHash",
        sr."UUID",
        sr.status,
        sr.logdata,
        sr.qr_link,
        sr."branchId",
        sr."CreatedDate",
        sr."CreatedUser",
        sr."ModifiedDate",
        sr."ModifiedUser",
        sr."reason",
        sr."GodownId",
        sr."creditPeriod",
        p."PricingLevelName",
        l."ledgerName",
        c."CostCentre"
    FROM public."tbl_SalesReturnMaster" sr
    LEFT JOIN public."tbl_PricingLevel" p ON p."PricingLevelId" = sr."pricingLevelId"
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = sr."ledgerId"
    LEFT JOIN public."tbl_CostCentre" c ON c."costCentreId" = sr."costCentreId"
    WHERE sr."ReturnMasterId" = ReturnMasterId
    ORDER BY sr."ReturnMasterId";
END;
$$;


ALTER FUNCTION public.get_sales_return_master_data_byid(returnmasterid bigint) OWNER TO postgres;

--
-- TOC entry 320 (class 1255 OID 45648)
-- Name: get_sales_return_master_list(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_sales_return_master_list() RETURNS TABLE("ReturnMasterId" bigint, "voucherNo" character varying, "suffixPrefixId" integer, "returnNo" character varying, date timestamp without time zone, "billTime" character varying, "salesMasterId" integer, "ledgerId" integer, "salesAccountId" integer, "pricingLevelId" integer, "employeeId" integer, "costCentreId" integer, "BatchId" character varying, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "partyName" character varying, "partyAddress" text, "partyMobile" character varying, "partyVatNo" character varying, "partyRefNo" character varying, "partyRefDate" timestamp without time zone, "lrNo" character varying, "transportCompany" character varying, narration text, "taxType" character varying, "taxableAmt" numeric, "subTotal" numeric, "totalTax" numeric, "additionalCost" numeric, "billDiscount" numeric, "roundOff" numeric, "totalAmount" numeric, "paymentMode" character varying, "CashLedgerId" integer, "CashRefNo" character varying, "CashAmount" numeric, "BankLedgerId" integer, "BankRefNo" character varying, "BankAmount" numeric, "BillBalanceAmount" numeric, "billDiscOnProductStatus" boolean, "AddCostonProductStatus" boolean, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "AgainstNo" character varying, "isVan" boolean, "vanExecutiveId" character varying, "preinvoiceHash" character varying, "currentinvoiceHash" character varying, "UUID" character varying, status character varying, logdata character varying, qr_link character varying, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying, reason text, "GodownId" integer, "creditPeriod" integer, "LedgerName" character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        sr."ReturnMasterId",
        sr."voucherNo",
        sr."suffixPrefixId",
        sr."returnNo",
        sr.date,
        sr."billTime",
        sr."salesMasterId",
        sr."ledgerId",
        sr."salesAccountId",
        sr."pricingLevelId",
        sr."employeeId",
        sr."costCentreId",
        sr."BatchId",
        sr."currencyConversionId",
        sr."exchangeRate",
        sr."exchangeDate",
        sr."partyName",
        sr."partyAddress",
        sr."partyMobile",
        sr."partyVatNo",
        sr."partyRefNo",
        sr."partyRefDate",
        sr."lrNo",
        sr."transportCompany",
        sr.narration,
        sr."taxType",
        sr."taxableAmt",
        sr."subTotal",
        sr."totalTax",
        sr."additionalCost",
        sr."billDiscount",
        sr."roundOff",
        sr."totalAmount",
        sr."paymentMode",
        sr."CashLedgerId",
        sr."CashRefNo",
        sr."CashAmount",
        sr."BankLedgerId",
        sr."BankRefNo",
        sr."BankAmount",
        sr."BillBalanceAmount",
        sr."billDiscOnProductStatus",
        sr."AddCostonProductStatus",
        sr."postedStatus",
        sr."postedBy",
        sr."postedDate",
        sr."AgainstNo",
        sr."isVan",
        sr."vanExecutiveId",
        sr."preinvoiceHash",
        sr."currentinvoiceHash",
        sr."UUID",
        sr.status,
        sr.logdata,
        sr.qr_link,
        sr."branchId",
        sr."CreatedDate",
        sr."CreatedUser",
        sr."ModifiedDate",
        sr."ModifiedUser",
        sr."reason",
        sr."GodownId",
        sr."creditPeriod",
        l."ledgerName" AS "LedgerName"
    FROM public."tbl_SalesReturnMaster" sr
    LEFT JOIN public."tbl_AccountLedger" l ON l."ledgerId" = sr."ledgerId"
    ORDER BY sr."ReturnMasterId" DESC;
END;
$$;


ALTER FUNCTION public.get_sales_return_master_list() OWNER TO postgres;

--
-- TOC entry 386 (class 1255 OID 62161)
-- Name: insert_delivery_note_details1(bigint, integer, character varying, integer, numeric, numeric, numeric, integer, numeric, numeric, text, numeric, numeric, numeric, character varying, numeric, numeric, numeric, character varying, numeric, numeric, text, integer, integer, character varying, integer, integer, integer, integer, timestamp without time zone, character varying, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_delivery_note_details1("deliveryNoteDetails1Id" bigint, "deliveryNoteMasterId" integer, "productCode" character varying, "SlNo" integer, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "ConversionFactor" numeric, barcode text, "discountPercentage" numeric, "taxAmount" numeric, "grossAmount" numeric, "taxType" character varying, "netAmount" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "PurchaseRate" numeric, otherchargeonproduct numeric, "Remark" text, "taxId" integer, "GodownId" integer, "RackId" character varying, "orderDetails1Id" integer, "quotationDetailsId" integer, "proformaDetails1Id" integer, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    "out_deliveryNoteDetails1Id" BIGINT;
BEGIN
    INSERT INTO public."tbl_DeliveryNoteDetails1" (
        "deliveryNoteDetails1Id",
        "deliveryNoteMasterId",
        "productCode",
        "SlNo",
        "qty",
        "freeQty",
        "rate",
        "unitId",
        "amount",
        "ConversionFactor",
        "barcode",
        "discountPercentage",
        "taxAmount",
        "grossAmount",
        "taxType",
        "netAmount",
        "billDiscOnProduct",
        "AddCostonProduct",
        "productDescription",
        "PurchaseRate",
        "otherchargeonproduct",
        "Remark",
        "taxId",
        "GodownId",
        "RackId",
        "orderDetails1Id",
        "quotationDetailsId",
        "proformaDetails1Id",
        "branchId",
        "CreatedDate",
        "CreatedUser",
        "ModifiedDate",
        "ModifiedUser"
    )
    VALUES (
        "deliveryNoteDetails1Id",
        "deliveryNoteMasterId",
        "productCode",
        "SlNo",
        "qty",
        "freeQty",
        "rate",
        "unitId",
        "amount",
        "ConversionFactor",
        "barcode",
        "discountPercentage",
        "taxAmount",
        "grossAmount",
        "taxType",
        "netAmount",
        "billDiscOnProduct",
        "AddCostonProduct",
        "productDescription",
        "PurchaseRate",
        "otherchargeonproduct",
        "Remark",
        "taxId",
        "GodownId",
        "RackId",
        "orderDetails1Id",
        "quotationDetailsId",
        "proformaDetails1Id",
        "branchId",
        "CreatedDate",
        "CreatedUser",
        "ModifiedDate",
        "ModifiedUser"
    )
    RETURNING public."tbl_DeliveryNoteDetails1"."deliveryNoteDetails1Id" INTO "out_deliveryNoteDetails1Id";
	
    RETURN "out_deliveryNoteDetails1Id";
END;
$$;


ALTER FUNCTION public.insert_delivery_note_details1("deliveryNoteDetails1Id" bigint, "deliveryNoteMasterId" integer, "productCode" character varying, "SlNo" integer, qty numeric, "freeQty" numeric, rate numeric, "unitId" integer, amount numeric, "ConversionFactor" numeric, barcode text, "discountPercentage" numeric, "taxAmount" numeric, "grossAmount" numeric, "taxType" character varying, "netAmount" numeric, "billDiscOnProduct" numeric, "AddCostonProduct" numeric, "productDescription" character varying, "PurchaseRate" numeric, otherchargeonproduct numeric, "Remark" text, "taxId" integer, "GodownId" integer, "RackId" character varying, "orderDetails1Id" integer, "quotationDetailsId" integer, "proformaDetails1Id" integer, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying, "ModifiedDate" timestamp without time zone, "ModifiedUser" character varying) OWNER TO postgres;

--
-- TOC entry 365 (class 1255 OID 62150)
-- Name: insert_delivery_note_master(bigint, integer, character varying, character varying, timestamp without time zone, integer, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, integer, integer, integer, text, integer, numeric, timestamp without time zone, character varying, timestamp without time zone, character varying, integer, integer, integer, character varying, character varying, numeric, numeric, numeric, numeric, numeric, numeric, numeric, integer, numeric, numeric, character varying, character varying, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, integer, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_delivery_note_master("deliveryNoteMasterId" bigint, "suffixPrefixId" integer, "voucherNo" character varying, "deliveryNoteNo" character varying, date timestamp without time zone, "ledgerId" integer, "customerName" character varying, "CustomerAddress" character varying, "CustomerPhone" character varying, "CustomerVATNo" character varying, "RefNo" character varying, "RefDate" timestamp without time zone, "costCentreId" integer, "pricingLevelId" integer, "employeeId" integer, narration text, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "LPONo" character varying, "LPODate" timestamp without time zone, "AgainstNo" character varying, "orderMasterId" integer, "quotationMasterId" integer, "proformaMasterId" integer, "BatchId" character varying, "taxType" character varying, "totalAmount" numeric, "taxableAmt" numeric, "additionalCost" numeric, "totalTax" numeric, "billDiscount" numeric, "roundOff" numeric, othercharge numeric, "otherChargeLedgerId" integer, "taxTotal" numeric, "grandTotal" numeric, "transportCompany" character varying, "deliveryLocation" character varying, "driverName" character varying, "contactNo" character varying, "deliveryVehicleNo" character varying, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    "out_deliveryNoteMasterId" BIGINT;
BEGIN
    INSERT INTO public."tbl_DeliveryNoteMaster"(
        "deliveryNoteMasterId",
        "suffixPrefixId",
        "voucherNo",
        "deliveryNoteNo",
        "date",
        "ledgerId",
        "customerName",
        "CustomerAddress",
        "CustomerPhone",
        "CustomerVATNo",
        "RefNo",
        "RefDate",
        "costCentreId",
        "pricingLevelId",
        "employeeId",
        "narration",
        "currencyConversionId",
        "exchangeRate",
        "exchangeDate",
        "LPONo",
        "LPODate",
        "AgainstNo",
        "orderMasterId",
        "quotationMasterId",
        "proformaMasterId",
        "BatchId",
        "taxType",
        "totalAmount",
        "taxableAmt",
        "additionalCost",
        "totalTax",
        "billDiscount",
        "roundOff",
        "othercharge",
        "otherChargeLedgerId",
        "taxTotal",
        "grandTotal",
        "transportCompany",
        "deliveryLocation",
        "driverName",
        "contactNo",
        "deliveryVehicleNo",
        "postedStatus",
        "postedBy",
        "postedDate",
        "branchId",
        "CreatedDate",
        "CreatedUser"
    )
    VALUES (
        "deliveryNoteMasterId",
        "suffixPrefixId",
        "voucherNo",
        "deliveryNoteNo",
        "date",
        "ledgerId",
        "customerName",
        "CustomerAddress",
        "CustomerPhone",
        "CustomerVATNo",
        "RefNo",
        "RefDate",
        "costCentreId",
        "pricingLevelId",
        "employeeId",
        "narration",
        "currencyConversionId",
        "exchangeRate",
        "exchangeDate",
        "LPONo",
        "LPODate",
        "AgainstNo",
        "orderMasterId",
        "quotationMasterId",
        "proformaMasterId",
        "BatchId",
        "taxType",
        "totalAmount",
        "taxableAmt",
        "additionalCost",
        "totalTax",
        "billDiscount",
        "roundOff",
        "othercharge",
        "otherChargeLedgerId",
        "taxTotal",
        "grandTotal",
        "transportCompany",
        "deliveryLocation",
        "driverName",
        "contactNo",
        "deliveryVehicleNo",
        "postedStatus",
        "postedBy",
        "postedDate",
        "branchId",
        "CreatedDate",
        "CreatedUser"
    )
    RETURNING public."tbl_DeliveryNoteMaster"."deliveryNoteMasterId" INTO "out_deliveryNoteMasterId";

    RETURN "out_deliveryNoteMasterId";
END;
$$;


ALTER FUNCTION public.insert_delivery_note_master("deliveryNoteMasterId" bigint, "suffixPrefixId" integer, "voucherNo" character varying, "deliveryNoteNo" character varying, date timestamp without time zone, "ledgerId" integer, "customerName" character varying, "CustomerAddress" character varying, "CustomerPhone" character varying, "CustomerVATNo" character varying, "RefNo" character varying, "RefDate" timestamp without time zone, "costCentreId" integer, "pricingLevelId" integer, "employeeId" integer, narration text, "currencyConversionId" integer, "exchangeRate" numeric, "exchangeDate" timestamp without time zone, "LPONo" character varying, "LPODate" timestamp without time zone, "AgainstNo" character varying, "orderMasterId" integer, "quotationMasterId" integer, "proformaMasterId" integer, "BatchId" character varying, "taxType" character varying, "totalAmount" numeric, "taxableAmt" numeric, "additionalCost" numeric, "totalTax" numeric, "billDiscount" numeric, "roundOff" numeric, othercharge numeric, "otherChargeLedgerId" integer, "taxTotal" numeric, "grandTotal" numeric, "transportCompany" character varying, "deliveryLocation" character varying, "driverName" character varying, "contactNo" character varying, "deliveryVehicleNo" character varying, "postedStatus" character varying, "postedBy" character varying, "postedDate" timestamp without time zone, "branchId" integer, "CreatedDate" timestamp without time zone, "CreatedUser" character varying) OWNER TO postgres;

--
-- TOC entry 319 (class 1255 OID 27434)
-- Name: insert_ledger_posting(timestamp without time zone, character varying, integer, integer, numeric, numeric, character varying, text, integer, character varying, numeric, timestamp without time zone, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_ledger_posting(date timestamp without time zone, vouchertype character varying, voucherno integer, ledgerid integer, debit numeric, credit numeric, invoiceno character varying, narration text, costcentreid integer, postingtype character varying, exchangerate numeric, exchangedate timestamp without time zone, branchid integer, createduser character varying) RETURNS TABLE(out_ledgerpostingid bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO "tbl_LedgerPosting" (
        date, "voucherType", "voucherNo", "ledgerId", debit, credit,
        "InvoiceNo", "Narration", "costCentreId", "PostingType",
        "exchangeRate", "exchangeDate", "branchId", "CreatedDate", "CreatedUser"
    )
    VALUES (
        date, voucherType, voucherNo, ledgerId, debit, credit,
        InvoiceNo, Narration, costCentreId, PostingType,
        exchangeRate, exchangeDate, branchId, NOW(), CreatedUser
    )
    RETURNING "ledgerPostingId" INTO out_ledgerPostingId;

    RETURN QUERY
    SELECT out_ledgerPostingId, 'Ledger posting inserted successfully!';
END;
$$;


ALTER FUNCTION public.insert_ledger_posting(date timestamp without time zone, vouchertype character varying, voucherno integer, ledgerid integer, debit numeric, credit numeric, invoiceno character varying, narration text, costcentreid integer, postingtype character varying, exchangerate numeric, exchangedate timestamp without time zone, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 402 (class 1255 OID 27438)
-- Name: insert_party_balance(timestamp without time zone, integer, character varying, integer, character varying, character varying, character varying, numeric, numeric, integer, integer, character varying, character varying, numeric, timestamp without time zone, integer, numeric, timestamp without time zone, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_party_balance(date timestamp without time zone, ledgerid integer, vouchertype character varying, voucherno integer, againstvouchertype character varying, againstvoucherno character varying, referencetype character varying, debit numeric, credit numeric, creditperiod integer, currencyconversionid integer, invoiceno character varying, referenceno character varying, billamount numeric, invoicedate timestamp without time zone, costcentreid integer, exchangerate numeric, exchangedate timestamp without time zone, branchid integer, createduser character varying) RETURNS TABLE(out_balanceid bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO public."tbl_Partybalance" (
        date, "ledgerId", "voucherType", "voucherNo", "againstVoucherType",
        "againstvoucherNo", "referenceType", debit, credit, "creditPeriod",
        "currencyConversionId", "invoiceNo", "referenceNo", "BillAmount",
        "invoiceDate", "costCentreId", "exchangeRate", "exchangeDate",
        "branchId", "CreatedDate", "CreatedUser"
    )
    VALUES (
        date, ledgerId, voucherType, voucherNo, againstVoucherType,
        againstVoucherNo, referenceType, debit, credit, creditPeriod,
        currencyConversionId, invoiceNo, referenceNo, BillAmount,
        invoiceDate, costCentreId, exchangeRate, exchangeDate,
        branchId, NOW(), CreatedUser
    )
    RETURNING "balanceId" INTO out_balanceId;

    RETURN QUERY SELECT out_balanceId, 'Party balance inserted successfully!'::TEXT;
END;
$$;


ALTER FUNCTION public.insert_party_balance(date timestamp without time zone, ledgerid integer, vouchertype character varying, voucherno integer, againstvouchertype character varying, againstvoucherno character varying, referencetype character varying, debit numeric, credit numeric, creditperiod integer, currencyconversionid integer, invoiceno character varying, referenceno character varying, billamount numeric, invoicedate timestamp without time zone, costcentreid integer, exchangerate numeric, exchangedate timestamp without time zone, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 311 (class 1255 OID 37446)
-- Name: insert_proforma_invoice_details1(bigint, integer, character varying, numeric, numeric, numeric, integer, numeric, integer, numeric, text, text, numeric, integer, character varying, numeric, numeric, numeric, numeric, numeric, numeric, integer, character varying, numeric, integer, timestamp without time zone, character varying, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_proforma_invoice_details1(proformadetails1id bigint, proformamasterid integer, productcode character varying, qty numeric, freeqty numeric, rate numeric, unitid integer, amount numeric, quotationdetailsid integer, conversionfactor numeric, barcode text, productdescription text, discountpercentage numeric, taxid integer, taxtype character varying, taxamount numeric, grossamount numeric, netamount numeric, purchaserate numeric, billdisconproduct numeric, addcostonproduct numeric, slno integer, size character varying, otherchargeonproduct numeric, branchid integer, createddate timestamp without time zone, createduser character varying, modifieddate timestamp without time zone, modifieduser character varying) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    out_proformaDetails1Id BIGINT;
BEGIN
    INSERT INTO public."tbl_proformaInvoiceDetails1" (
        "proformaDetails1Id", "proformaMasterId", "productCode", qty, "freeQty", rate, 
        "unitId", amount, "quotationDetailsId", "ConversionFactor", barcode, 
        "productDescription", "discountPercentage", "taxId", "taxType", "taxAmount", 
        "grossAmount", "netAmount", "PurchaseRate", "billDiscOnProduct", 
        "AddCostonProduct", "SlNo", "Size", otherchargeonproduct, "branchId", 
        "CreatedDate", "CreatedUser", "ModifiedDate", "ModifiedUser"
    )
    VALUES (
        proformaDetails1Id, proformaMasterId, productCode, qty, freeQty, rate, 
        unitId, amount, quotationDetailsId, ConversionFactor, barcode, 
        productDescription, discountPercentage, taxId, taxType, taxAmount, 
        grossAmount, netAmount, PurchaseRate, billDiscOnProduct, 
        AddCostonProduct, SlNo, Size, otherchargeonproduct, branchId, 
        CreatedDate, CreatedUser, ModifiedDate, ModifiedUser
    )
    RETURNING "proformaDetails1Id" INTO out_proformaDetails1Id;

    RETURN out_proformaDetails1Id;
END;
$$;


ALTER FUNCTION public.insert_proforma_invoice_details1(proformadetails1id bigint, proformamasterid integer, productcode character varying, qty numeric, freeqty numeric, rate numeric, unitid integer, amount numeric, quotationdetailsid integer, conversionfactor numeric, barcode text, productdescription text, discountpercentage numeric, taxid integer, taxtype character varying, taxamount numeric, grossamount numeric, netamount numeric, purchaserate numeric, billdisconproduct numeric, addcostonproduct numeric, slno integer, size character varying, otherchargeonproduct numeric, branchid integer, createddate timestamp without time zone, createduser character varying, modifieddate timestamp without time zone, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 307 (class 1255 OID 37454)
-- Name: insert_proforma_invoice_master(bigint, integer, character varying, character varying, timestamp without time zone, timestamp without time zone, boolean, integer, character varying, text, character varying, character varying, character varying, timestamp without time zone, character varying, character varying, integer, character varying, integer, integer, text, integer, numeric, timestamp without time zone, integer, character varying, character varying, timestamp without time zone, timestamp without time zone, character varying, character varying, character varying, character varying, numeric, numeric, numeric, numeric, character varying, numeric, numeric, numeric, numeric, numeric, integer, timestamp without time zone, character varying, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_proforma_invoice_master(proformamasterid bigint, suffixprefixid integer, voucherno character varying, proformano character varying, date timestamp without time zone, duedate timestamp without time zone, cancelled boolean, ledgerid integer, partyname character varying, partyaddress text, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, status character varying, taxtype character varying, quotationmasterid integer, againstno character varying, pricinglevelid integer, employeeid integer, narration text, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, costcentreid integer, batchid character varying, lpono character varying, lpodate timestamp without time zone, deliverydate timestamp without time zone, deliveryterms character varying, paymentterms character varying, lrno character varying, transportcompany character varying, subtotal numeric, additionalcost numeric, billdiscount numeric, othercharge numeric, otherchargeledgerid character varying, taxableamt numeric, totaltax numeric, roundoff numeric, totalamount numeric, advanceperc numeric, branchid integer, createddate timestamp without time zone, createduser character varying, modifieddate timestamp without time zone, modifieduser character varying) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    out_proformaMasterId BIGINT;
BEGIN
    INSERT INTO public."tbl_proformaInvoiceMaster" (
        "proformaMasterId", "suffixPrefixId", "voucherNo", "proformaNo",
        date, "dueDate", cancelled, "ledgerId", "partyName", "partyAddress",
        "partyMobile", "partyVatNo", "partyRefNo", "partyRefDate", status,
        "taxType", "quotationMasterId", "AgainstNo", "pricingLevelId",
        "employeeId", narration, "currencyConversionId", "exchangeRate",
        "exchangeDate", "costCentreId", "BatchId", "LPONO", "LPODate",
        "deliveryDate", "DeliveryTerms", "PaymentTerms", "lrNo",
        "transportCompany", "subTotal", "additionalCost", "billDiscount",
        othercharge, "otherChargeLedgerId", "taxableAmt", "totalTax",
        "roundOff", "totalAmount", "AdvancePerc", "branchId",
        "CreatedDate", "CreatedUser", "ModifiedDate", "ModifiedUser"
    )
    VALUES (
        proformaMasterId, suffixPrefixId, voucherNo, proformaNo,
        date, dueDate, cancelled, ledgerId, partyName, partyAddress,
        partyMobile, partyVatNo, partyRefNo, partyRefDate, status,
        taxType, quotationMasterId, AgainstNo, pricingLevelId,
        employeeId, narration, currencyConversionId, exchangeRate,
        exchangeDate, costCentreId, BatchId, LPONO, LPODate,
        deliveryDate, DeliveryTerms, PaymentTerms, lrNo,
        transportCompany, subTotal, additionalCost, billDiscount,
        othercharge, otherChargeLedgerId, taxableAmt, totalTax,
        roundOff, totalAmount, AdvancePerc, branchId,
        CreatedDate, CreatedUser, ModifiedDate, ModifiedUser
    )
    RETURNING "proformaMasterId" INTO out_proformaMasterId;

    RETURN out_proformaMasterId;
END;
$$;


ALTER FUNCTION public.insert_proforma_invoice_master(proformamasterid bigint, suffixprefixid integer, voucherno character varying, proformano character varying, date timestamp without time zone, duedate timestamp without time zone, cancelled boolean, ledgerid integer, partyname character varying, partyaddress text, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, status character varying, taxtype character varying, quotationmasterid integer, againstno character varying, pricinglevelid integer, employeeid integer, narration text, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, costcentreid integer, batchid character varying, lpono character varying, lpodate timestamp without time zone, deliverydate timestamp without time zone, deliveryterms character varying, paymentterms character varying, lrno character varying, transportcompany character varying, subtotal numeric, additionalcost numeric, billdiscount numeric, othercharge numeric, otherchargeledgerid character varying, taxableamt numeric, totaltax numeric, roundoff numeric, totalamount numeric, advanceperc numeric, branchid integer, createddate timestamp without time zone, createduser character varying, modifieddate timestamp without time zone, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 352 (class 1255 OID 37290)
-- Name: insert_quotation_details(integer, character varying, integer, integer, numeric, numeric, numeric, numeric, numeric, numeric, numeric, numeric, character varying, numeric, numeric, character varying, numeric, numeric, character varying, integer, numeric, numeric, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_quotation_details(quotationmasterid integer, productcode character varying, unitid integer, taxid integer, qty numeric, freeqty numeric, rate numeric, amount numeric, discountpercentage numeric, discountamount numeric, taxamount numeric, grossamount numeric, taxtype character varying, netamount numeric, conversionfactor numeric, barcode character varying, billdisconproduct numeric, addcostonproduct numeric, productdescription character varying, slno integer, purchaserate numeric, otherchargeonproduct numeric, branchid integer, createduser character varying) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_quotationDetailsId BIGINT;
BEGIN
    INSERT INTO public."tbl_QuotationDetails" (
        "quotationMasterId", "productCode", "unitId", "taxId",
        qty, "freeQty", rate, amount, "discountPercentage", "discountAmount",
        "taxAmount", "grossAmount", "taxType", "netAmount", "ConversionFactor",
        barcode, "billDiscOnProduct", "AddCostonProduct", "productDescription",
        "SlNo", "PurchaseRate", otherchargeonproduct, "branchId",
        "CreatedDate", "CreatedUser", "ModifiedDate", "ModifiedUser"
    )
    VALUES (
        quotationMasterId, productCode, unitId, taxId,
        qty, freeQty, rate, amount, discountPercentage, discountAmount,
        taxAmount, grossAmount, taxType, netAmount, ConversionFactor,
        barcode, billDiscOnProduct, AddCostonProduct, productDescription,
        SlNo, PurchaseRate, otherchargeonproduct, branchId,
        NOW(), CreatedUser, NULL, NULL
    )
    RETURNING "quotationDetailsId" INTO v_quotationDetailsId;

    RETURN v_quotationDetailsId;
END;
$$;


ALTER FUNCTION public.insert_quotation_details(quotationmasterid integer, productcode character varying, unitid integer, taxid integer, qty numeric, freeqty numeric, rate numeric, amount numeric, discountpercentage numeric, discountamount numeric, taxamount numeric, grossamount numeric, taxtype character varying, netamount numeric, conversionfactor numeric, barcode character varying, billdisconproduct numeric, addcostonproduct numeric, productdescription character varying, slno integer, purchaserate numeric, otherchargeonproduct numeric, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 370 (class 1255 OID 37289)
-- Name: insert_quotation_master(bigint, character varying, character varying, integer, integer, integer, timestamp without time zone, integer, numeric, timestamp without time zone, character varying, integer, character varying, text, character varying, character varying, integer, integer, text, character varying, timestamp without time zone, character varying, numeric, numeric, numeric, character varying, numeric, numeric, numeric, numeric, numeric, boolean, boolean, boolean, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, timestamp without time zone, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_quotation_master(quotationmasterid bigint, voucherno character varying, quotationno character varying, suffixprefixid integer, employeeid integer, pricinglevelid integer, date timestamp without time zone, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, batchid character varying, ledgerid integer, customername character varying, customeraddress text, customerphone character varying, customervatno character varying, creditperiod integer, costcentreid integer, narration text, partyrefno character varying, partyrefdate timestamp without time zone, taxtype character varying, totalamount numeric, billdiscount numeric, additionalcost numeric, otherchargeledgerid character varying, othercharge numeric, taxableamt numeric, totaltax numeric, roundoff numeric, grandtotal numeric, billdisconproductstatus boolean, addcostonproductstatus boolean, approved boolean, contactno character varying, contactperson character varying, deliveredwithin character varying, deliverysite character varying, paymentterms character varying, quatationvalidity character varying, deliveryterms character varying, certificate character varying, incoterms character varying, email character varying, carmake character varying, carmodel character varying, kilometer character varying, nextservice character varying, vehicleintime timestamp without time zone, vehicleouttime timestamp without time zone, branchid integer, createduser character varying) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    out_quotationMasterId BIGINT;
BEGIN
    INSERT INTO public."tbl_QuotationMaster" (
        "quotationMasterId", "voucherNo", "quotationNo", "suffixPrefixId",
        "employeeId", "pricingLevelId", date, "currencyConversionId",
        "exchangeRate", "exchangeDate", "BatchId", "ledgerId",
        "customerName", "CustomerAddress", "CustomerPhone", "CustomerVatNo",
        "creditPeriod", "costCentreId", narration, "partyRefNo", "partyRefDate",
        "taxType", "totalAmount", "billDiscount", "additionalCost", "otherChargeLedgerId",
        othercharge, "taxableAmt", "totalTax", "roundOff", "grandTotal",
        "billDiscOnProductStatus", "AddCostonProductStatus", approved, contactno,
        contactperson, deliveredwithin, deliverysite, paymentterms, quatationvalidity,
        "DeliveryTerms", "Certificate", "IncoTerms", "Email", "CarMake",
        "CarModel", "Kilometer", "NextService", "VehicleInTime", "VehicleOutTime",
        "branchId", "CreatedDate", "CreatedUser", "ModifiedDate", "ModifiedUser"
    )
    VALUES (
        quotationMasterId, voucherNo, quotationNo, suffixPrefixId,
        employeeId, pricingLevelId, date, currencyConversionId,
        exchangeRate, exchangeDate, BatchId, ledgerId,
        customerName, CustomerAddress, CustomerPhone, CustomerVatNo,
        creditPeriod, costCentreId, narration, partyRefNo, partyRefDate,
        taxType, totalAmount, billDiscount, additionalCost, otherChargeLedgerId,
        othercharge, taxableAmt, totalTax, roundOff, grandTotal,
        billDiscOnProductStatus, AddCostonProductStatus, approved, contactno,
        contactperson, deliveredwithin, deliverysite, paymentterms, quatationvalidity,
        DeliveryTerms, Certificate, IncoTerms, Email, CarMake,
        CarModel, Kilometer, NextService, VehicleInTime, VehicleOutTime,
        branchId, NOW(), CreatedUser, NULL, NULL
    )
    RETURNING "quotationMasterId" INTO out_quotationMasterId;

    RETURN out_quotationMasterId;
END;
$$;


ALTER FUNCTION public.insert_quotation_master(quotationmasterid bigint, voucherno character varying, quotationno character varying, suffixprefixid integer, employeeid integer, pricinglevelid integer, date timestamp without time zone, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, batchid character varying, ledgerid integer, customername character varying, customeraddress text, customerphone character varying, customervatno character varying, creditperiod integer, costcentreid integer, narration text, partyrefno character varying, partyrefdate timestamp without time zone, taxtype character varying, totalamount numeric, billdiscount numeric, additionalcost numeric, otherchargeledgerid character varying, othercharge numeric, taxableamt numeric, totaltax numeric, roundoff numeric, grandtotal numeric, billdisconproductstatus boolean, addcostonproductstatus boolean, approved boolean, contactno character varying, contactperson character varying, deliveredwithin character varying, deliverysite character varying, paymentterms character varying, quatationvalidity character varying, deliveryterms character varying, certificate character varying, incoterms character varying, email character varying, carmake character varying, carmodel character varying, kilometer character varying, nextservice character varying, vehicleintime timestamp without time zone, vehicleouttime timestamp without time zone, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 334 (class 1255 OID 27494)
-- Name: insert_receipt_details(bigint, integer, integer, numeric, text, character varying, timestamp without time zone, integer, numeric, timestamp without time zone, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_receipt_details(receiptmasterid bigint, slno integer, ledgerid integer, amount numeric, narration text, chequeno character varying, chequedate timestamp without time zone, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, branchid integer, createduser character varying) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    receiptDetailsId BIGINT;
BEGIN
    INSERT INTO public."tbl_Receiptdetails" (
        "receiptMasterId",
        "SlNo",
        "ledgerId",
        amount,
        "Narration",
        "chequeNo",
        "chequeDate",
        "currencyConversionId",
        "exchangeRate",
        "exchangeDate",
        "branchId",
        "CreatedDate",
        "CreatedUser"
    )
    VALUES (
        receiptMasterId,
        SlNo,
        ledgerId,
        amount,
        Narration,
        chequeNo,
        chequeDate,
        currencyConversionId,
        exchangeRate,
        exchangeDate,
        branchId,
        (NOW() AT TIME ZONE 'Asia/Kolkata'),
        CreatedUser
    )
    RETURNING "receiptDetailsId" INTO receiptDetailsId;

    RETURN receiptDetailsId;
END;
$$;


ALTER FUNCTION public.insert_receipt_details(receiptmasterid bigint, slno integer, ledgerid integer, amount numeric, narration text, chequeno character varying, chequedate timestamp without time zone, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 338 (class 1255 OID 27490)
-- Name: insert_receipt_master(character varying, integer, character varying, timestamp without time zone, integer, text, numeric, character varying, integer, integer, character varying, timestamp without time zone, character varying, character varying, timestamp without time zone, character varying, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_receipt_master(voucherno character varying, suffixprefixid integer, receiptno character varying, date timestamp without time zone, ledgerid integer, narration text, totalamount numeric, userid character varying, employeeid integer, costcentreid integer, referenceno character varying, referencedate timestamp without time zone, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, vanexecutiveid character varying, branchid integer, createduser character varying) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    receiptMasterId BIGINT;
BEGIN
    INSERT INTO public."tbl_ReceiptMaster"(
        "voucherNo",
        "suffixPrefixId",
        "receiptNo",
        date,
        "ledgerId",
        narration,
        "totalAmount",
        "userId",
        "employeeId",
        "costCentreId",
        "ReferenceNo",
        "ReferenceDate",
        "postedStatus",
        "postedBy",
        "postedDate",
        "vanExecutiveId",
        "branchId",
        "CreatedUser",
        "CreatedDate"
    )
    VALUES (
        voucherNo,
        suffixPrefixId,
        receiptNo,
        date,
        ledgerId,
        narration,
        totalAmount,
        userId,
        employeeId,
        costCentreId,
        ReferenceNo,
        ReferenceDate,
        postedStatus,
        postedBy,
        postedDate,
        vanExecutiveId,
        branchId,
        CreatedUser,
        (NOW() AT TIME ZONE 'Asia/Kolkata')
    )
    RETURNING "receiptMasterId" INTO receiptMasterId;

    RETURN receiptMasterId;
END;
$$;


ALTER FUNCTION public.insert_receipt_master(voucherno character varying, suffixprefixid integer, receiptno character varying, date timestamp without time zone, ledgerid integer, narration text, totalamount numeric, userid character varying, employeeid integer, costcentreid integer, referenceno character varying, referencedate timestamp without time zone, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, vanexecutiveid character varying, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 384 (class 1255 OID 27453)
-- Name: insert_sales_details1(integer, character varying, character varying, character varying, character varying, integer, character varying, numeric, numeric, numeric, integer, numeric, integer, character varying, numeric, text, numeric, numeric, numeric, numeric, numeric, text, numeric, numeric, numeric, integer, integer, integer, integer, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_sales_details1(salesmasterid integer, deliverynotedetails1id character varying, orderdetails1id character varying, quotationdetailsid character varying, proformadetails1id character varying, slno integer, productcode character varying, qty numeric, freeqty numeric, rate numeric, unitid integer, discountpercentage numeric, taxid integer, taxtype character varying, conversionfactor numeric, barcode text, purchaserate numeric, taxamount numeric, grossamount numeric, netamount numeric, amount numeric, productdescription text, billdisconproduct numeric, addcostonproduct numeric, otherchargeonproduct numeric, salesmanid integer, godownid integer, rackid integer, branchid integer, createduser character varying, modifieduser character varying) RETURNS TABLE(out_salesdetails1id bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO "tbl_SalesDetails1" (
        "salesMasterId",
        "deliveryNoteDetails1Id",
        "orderDetails1Id",
        "quotationDetailsId",
        "proformaDetails1Id",
        "SlNo",
        "productCode",
        qty,
        "freeQty",
        rate,
        "unitId",
        "discountPercentage",
        "taxId",
        "taxType",
        "ConversionFactor",
        barcode,
        "PurchaseRate",
        "taxAmount",
        "grossAmount",
        "netAmount",
        amount,
        "productDescription",
        "billDiscOnProduct",
        "AddCostonProduct",
        otherchargeonproduct,
        "salesManId",
        "GodownId",
        "RackId",
        "branchId",
        "CreatedDate",
        "CreatedUser",
		"ModifiedDate",
        "ModifiedUser"
    )
    VALUES (
        salesMasterId,
        deliveryNoteDetails1Id,
        orderDetails1Id,
        quotationDetailsId,
        proformaDetails1Id,
        SlNo,
        productCode,
        qty,
        freeQty,
        rate,
        unitId,
        discountPercentage,
        taxId,
        taxType,
        ConversionFactor,
        barcode,
        PurchaseRate,
        taxAmount,
        grossAmount,
        netAmount,
        amount,
        productDescription,
        billDiscOnProduct,
        AddCostonProduct,
        otherchargeonproduct,
        salesManId,
        GodownId,
        RackId,
        branchId,
        NOW(),
        CreatedUser,
		NOW(),
        ModifiedUser
    )
    RETURNING "salesDetails1Id" INTO out_salesDetails1Id;

    RETURN QUERY
    SELECT out_salesDetails1Id, 'Sales details inserted successfully!'::text;
END;
$$;


ALTER FUNCTION public.insert_sales_details1(salesmasterid integer, deliverynotedetails1id character varying, orderdetails1id character varying, quotationdetailsid character varying, proformadetails1id character varying, slno integer, productcode character varying, qty numeric, freeqty numeric, rate numeric, unitid integer, discountpercentage numeric, taxid integer, taxtype character varying, conversionfactor numeric, barcode text, purchaserate numeric, taxamount numeric, grossamount numeric, netamount numeric, amount numeric, productdescription text, billdisconproduct numeric, addcostonproduct numeric, otherchargeonproduct numeric, salesmanid integer, godownid integer, rackid integer, branchid integer, createduser character varying, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 328 (class 1255 OID 27384)
-- Name: insert_sales_master(bigint, character varying, character varying, integer, timestamp without time zone, character varying, integer, integer, integer, integer, integer, character varying, integer, integer, integer, character varying, text, character varying, character varying, character varying, timestamp without time zone, character varying, timestamp without time zone, integer, timestamp without time zone, timestamp without time zone, numeric, timestamp without time zone, integer, integer, integer, integer, character varying, character varying, character varying, character varying, text, numeric, numeric, numeric, numeric, character varying, integer, numeric, numeric, numeric, numeric, numeric, numeric, integer, character varying, timestamp without time zone, numeric, numeric, character varying, integer, character varying, numeric, integer, character varying, numeric, numeric, character varying, character varying, character varying, character varying, timestamp without time zone, timestamp without time zone, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, boolean, boolean, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_sales_master(salesmasterid bigint, voucherno character varying, invoiceno character varying, suffixprefixid integer, date timestamp without time zone, billtime character varying, ledgerid integer, pricinglevelid integer, employeeid integer, salesaccount integer, currencyconversionid integer, taxtype character varying, godownid integer, costcentreid integer, batchid integer, customername character varying, customeraddress text, customerphone character varying, customervatno character varying, refno character varying, refdate timestamp without time zone, orderrefno character varying, orderrefdate timestamp without time zone, creditperiod integer, duedate timestamp without time zone, deliverydate timestamp without time zone, exchangerate numeric, exchangedate timestamp without time zone, deliverynotemasterid integer, ordermasterid integer, quotationmasterid integer, proformamasterid integer, againstno character varying, lrno character varying, transportcompany character varying, vehicleno character varying, narration text, taxableamt numeric, subtotal numeric, totaltax numeric, additionalcost numeric, otherchargeremark character varying, otherchargeledgerid integer, othercharge numeric, billdiscount numeric, roundoff numeric, totalamount numeric, adjustmentamount numeric, advanceperc numeric, retentionledgerid integer, retentiontype character varying, retentionduedate timestamp without time zone, retentionpercentage numeric, retentionamount numeric, paymentmode character varying, cashledgerid integer, cashrefno character varying, cashamount numeric, bankledgerid integer, bankrefno character varying, bankamount numeric, billbalanceamount numeric, carmake character varying, carmodel character varying, kilometer character varying, nextservice character varying, vehicleintime timestamp without time zone, vehicleouttime timestamp without time zone, preinvoicehash character varying, currentinvoicehash character varying, uuid character varying, zatcastatus character varying, logdata character varying, qr_link character varying, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, billdisconproductstatus boolean, addcostonproductstatus boolean, branchid integer, createduser character varying) RETURNS TABLE(out_salesmasterid bigint, out_voucherno character varying, out_invoiceno character varying, message text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_exists BOOLEAN;
BEGIN
    -- 1. Check duplicate invoice number
    SELECT EXISTS (
        SELECT 1 FROM "tbl_SalesMaster"
        WHERE LOWER("invoiceNo") = LOWER(invoiceNo)
    ) INTO v_exists;

    IF v_exists THEN
        RETURN QUERY SELECT NULL::BIGINT, NULL::VARCHAR, invoiceNo, 'Invoice Number Already Exists!'::TEXT;
        RETURN;
    END IF;

    -- 2. Insert record
    INSERT INTO "tbl_SalesMaster" (
        "salesMasterId", "voucherNo", "invoiceNo", "suffixPrefixId",
        "date", "billTime", "ledgerId", "pricingLevelId", "employeeId",
        "salesAccount", "currencyConversionId", "taxType", "GodownId", "costCentreId",
        "BatchId", "customerName", "CustomerAddress", "CustomerPhone", "customerVATNo",
        "RefNo", "refDate", "orderRefNo", "orderRefDate", "creditPeriod", "dueDate",
        "deliveryDate", "exchangeRate", "exchangeDate", "deliveryNoteMasterId",
        "orderMasterId", "quotationMasterId", "proformaMasterId", "AgainstNo", "lrNo",
        "transportCompany", "vehicleNo", narration, "taxableAmt", "subTotal",
        "totalTax", "additionalCost", "OtherChargeRemark", "otherChargeLedgerId",
        othercharge, "billDiscount", "roundOff", "totalAmount", "AdjustmentAmount",
        "AdvancePerc", "RetentionLedgerId", "RetentionType", "RetentionDueDate",
        "RetentionPercentage", "RetentionAmount", "paymentMode", "CashLedgerId",
        "CashRefNo", "CashAmount", "BankLedgerId", "BankRefNo", "BankAmount",
        "BillBalanceAmount", "CarMake", "CarModel", "Kilometer", "NextService",
        "VehicleInTime", "VehicleOutTime", "preinvoiceHash", "currentinvoiceHash",
        "UUID", "Zatcastatus", logdata, qr_link, "postedStatus", "postedBy",
        "postedDate", "billDiscOnProductStatus", "AddCostonProductStatus",
        "branchId", "CreatedDate", "CreatedUser"
    )
    VALUES (
        salesMasterId, voucherNo, invoiceNo, suffixPrefixId,
        date, billTime, ledgerId, pricingLevelId, employeeId,
        salesAccount, currencyConversionId, taxType, GodownId, costCentreId,
        BatchId, customerName, CustomerAddress, CustomerPhone, customerVATNo,
        RefNo, refDate, orderRefNo, orderRefDate, creditPeriod, dueDate,
        deliveryDate, exchangeRate, exchangeDate, deliveryNoteMasterId,
        orderMasterId, quotationMasterId, proformaMasterId, AgainstNo, lrNo,
        transportCompany, vehicleNo, narration, taxableAmt, subTotal,
        totalTax, additionalCost, OtherChargeRemark, otherChargeLedgerId,
        othercharge, billDiscount, roundOff, totalAmount, AdjustmentAmount,
        AdvancePerc, RetentionLedgerId, RetentionType, RetentionDueDate,
        RetentionPercentage, RetentionAmount, paymentMode, CashLedgerId,
        CashRefNo, CashAmount, BankLedgerId, BankRefNo, BankAmount,
        BillBalanceAmount, CarMake, CarModel, Kilometer, NextService,
        VehicleInTime, VehicleOutTime, preinvoiceHash, currentinvoiceHash,
        UUID, Zatcastatus, logdata, qr_link, postedStatus, postedBy,
        postedDate, billDiscOnProductStatus, AddCostonProductStatus,
        branchId, NOW(), CreatedUser
    );

    -- 3. Return success
    RETURN QUERY
    SELECT
        salesMasterId,
        voucherNo,
        invoiceNo,
        'Sales record inserted successfully!'::TEXT;
END;
$$;


ALTER FUNCTION public.insert_sales_master(salesmasterid bigint, voucherno character varying, invoiceno character varying, suffixprefixid integer, date timestamp without time zone, billtime character varying, ledgerid integer, pricinglevelid integer, employeeid integer, salesaccount integer, currencyconversionid integer, taxtype character varying, godownid integer, costcentreid integer, batchid integer, customername character varying, customeraddress text, customerphone character varying, customervatno character varying, refno character varying, refdate timestamp without time zone, orderrefno character varying, orderrefdate timestamp without time zone, creditperiod integer, duedate timestamp without time zone, deliverydate timestamp without time zone, exchangerate numeric, exchangedate timestamp without time zone, deliverynotemasterid integer, ordermasterid integer, quotationmasterid integer, proformamasterid integer, againstno character varying, lrno character varying, transportcompany character varying, vehicleno character varying, narration text, taxableamt numeric, subtotal numeric, totaltax numeric, additionalcost numeric, otherchargeremark character varying, otherchargeledgerid integer, othercharge numeric, billdiscount numeric, roundoff numeric, totalamount numeric, adjustmentamount numeric, advanceperc numeric, retentionledgerid integer, retentiontype character varying, retentionduedate timestamp without time zone, retentionpercentage numeric, retentionamount numeric, paymentmode character varying, cashledgerid integer, cashrefno character varying, cashamount numeric, bankledgerid integer, bankrefno character varying, bankamount numeric, billbalanceamount numeric, carmake character varying, carmodel character varying, kilometer character varying, nextservice character varying, vehicleintime timestamp without time zone, vehicleouttime timestamp without time zone, preinvoicehash character varying, currentinvoicehash character varying, uuid character varying, zatcastatus character varying, logdata character varying, qr_link character varying, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, billdisconproductstatus boolean, addcostonproductstatus boolean, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 342 (class 1255 OID 54048)
-- Name: insert_sales_order_details1(bigint, integer, integer, integer, character varying, character varying, character varying, numeric, numeric, numeric, integer, numeric, numeric, numeric, integer, character varying, numeric, numeric, numeric, numeric, numeric, numeric, numeric, integer, integer, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_sales_order_details1(orderdetails1id bigint, ordermasterid integer, quotationdetailsid integer, proformadetails1id integer, productcode character varying, productdescription character varying, barcode character varying, qty numeric, freeqty numeric, rate numeric, unitid integer, amount numeric, conversionfactor numeric, discountpercentage numeric, taxid integer, taxtype character varying, taxamount numeric, grossamount numeric, netamount numeric, purchaserate numeric, billdisconproduct numeric, addcostonproduct numeric, otherchargeonproduct numeric, slno integer, branchid integer, createddate timestamp without time zone, createduser character varying) RETURNS TABLE(out_orderdetails1id bigint, message text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_orderDetails1Id bigint;
BEGIN
    -- Generate new ID if null
    IF orderDetails1Id IS NULL THEN
        SELECT COALESCE(MAX("orderDetails1Id"), 0) + 1 INTO v_orderDetails1Id FROM public."tbl_SalesOrderdetails1";
    ELSE
        v_orderDetails1Id := orderDetails1Id;
    END IF;

    INSERT INTO public."tbl_SalesOrderdetails1" (
        "orderDetails1Id", "orderMasterId", "quotationDetailsId", "proformaDetails1Id",
        "productCode", "productDescription", barcode, qty, "freeQty", rate, "unitId", amount,
        "ConversionFactor", "discountPercentage", "taxId", "taxType", "taxAmount",
        "grossAmount", "netAmount", "PurchaseRate", "billDiscOnProduct", "AddCostonProduct",
        otherchargeonproduct, "SlNo", "branchId", "CreatedDate", "CreatedUser"
    )
    VALUES (
        v_orderDetails1Id, orderMasterId, quotationDetailsId, proformaDetails1Id,
        productCode, productDescription, barcode, qty, freeQty, rate, unitId, amount,
        ConversionFactor, discountPercentage, taxId, taxType, taxAmount,
        grossAmount, netAmount, PurchaseRate, billDiscOnProduct, AddCostonProduct,
        otherchargeonproduct, SlNo, branchId, CreatedDate, CreatedUser
    );

    RETURN QUERY
    SELECT v_orderDetails1Id, 'Sales Order Detail inserted successfully'::text;
END;
$$;


ALTER FUNCTION public.insert_sales_order_details1(orderdetails1id bigint, ordermasterid integer, quotationdetailsid integer, proformadetails1id integer, productcode character varying, productdescription character varying, barcode character varying, qty numeric, freeqty numeric, rate numeric, unitid integer, amount numeric, conversionfactor numeric, discountpercentage numeric, taxid integer, taxtype character varying, taxamount numeric, grossamount numeric, netamount numeric, purchaserate numeric, billdisconproduct numeric, addcostonproduct numeric, otherchargeonproduct numeric, slno integer, branchid integer, createddate timestamp without time zone, createduser character varying) OWNER TO postgres;

--
-- TOC entry 399 (class 1255 OID 54045)
-- Name: insert_sales_order_master(bigint, integer, character varying, character varying, timestamp without time zone, timestamp without time zone, integer, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, integer, integer, text, integer, integer, numeric, timestamp without time zone, character varying, integer, integer, character varying, timestamp without time zone, character varying, character varying, numeric, numeric, numeric, numeric, numeric, numeric, numeric, numeric, character varying, character varying, character varying, integer, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_sales_order_master(ordermasterid bigint, suffixprefixid integer, voucherno character varying, orderno character varying, date timestamp without time zone, duedate timestamp without time zone, ledgerid integer, partyname character varying, partyaddress character varying, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, pricinglevelid integer, employeeid integer, narration text, costcentreid integer, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, againstno character varying, quotationmasterid integer, proformamasterid integer, lpono character varying, lpodate timestamp without time zone, status character varying, taxtype character varying, subtotal numeric, additionalcost numeric, billdiscount numeric, othercharge numeric, taxableamt numeric, totaltax numeric, roundoff numeric, totalamount numeric, otherchargeledgerid character varying, lrno character varying, transportcompany character varying, branchid integer, createddate timestamp without time zone, createduser character varying) RETURNS TABLE(out_ordermasterid bigint, message text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_orderMasterId bigint;
BEGIN
    -- Generate new ID (if not using serial or identity column)
    IF orderMasterId IS NULL THEN
        SELECT COALESCE(MAX(orderMasterId), 0) + 1 INTO v_orderMasterId FROM public.tbl_SalesOrderMaster;
    ELSE
        v_orderMasterId := orderMasterId;
    END IF;

    INSERT INTO public."tbl_SalesOrderMaster" (
        "orderMasterId", "suffixPrefixId", "voucherNo", "orderNo", date, "dueDate", 
        "ledgerId", "partyName", "partyAddress", "partyMobile", "partyVatNo",
        "partyRefNo", "partyRefDate", "pricingLevelId", "employeeId", narration,
        "costCentreId", "currencyConversionId", "exchangeRate", "exchangeDate",
        "AgainstNo", "quotationMasterId", "proformaMasterId", "LPONO", "LPODate",
        status, "taxType", "subTotal", "additionalCost", "billDiscount", othercharge,
        "taxableAmt", "totalTax", "roundOff", "totalAmount", "otherChargeLedgerId",
        "lrNo", "transportCompany", "branchId", "CreatedDate", "CreatedUser"
    )
    VALUES (
        v_orderMasterId, suffixPrefixId, voucherNo, orderNo, date, dueDate,
        ledgerId, partyName, partyAddress, partyMobile, partyVatNo,
        partyRefNo, partyRefDate, pricingLevelId, employeeId, narration,
        costCentreId, currencyConversionId, exchangeRate, exchangeDate,
        AgainstNo, quotationMasterId, proformaMasterId, LPONO, LPODate,
        status, taxType, subTotal, additionalCost, billDiscount, othercharge,
        taxableAmt, totalTax, roundOff, totalAmount, otherChargeLedgerId,
        lrNo, transportCompany, branchId, CreatedDate, CreatedUser
    );

    RETURN QUERY
    SELECT v_orderMasterId, 'Sales Order Master inserted successfully'::text;
END;
$$;


ALTER FUNCTION public.insert_sales_order_master(ordermasterid bigint, suffixprefixid integer, voucherno character varying, orderno character varying, date timestamp without time zone, duedate timestamp without time zone, ledgerid integer, partyname character varying, partyaddress character varying, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, pricinglevelid integer, employeeid integer, narration text, costcentreid integer, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, againstno character varying, quotationmasterid integer, proformamasterid integer, lpono character varying, lpodate timestamp without time zone, status character varying, taxtype character varying, subtotal numeric, additionalcost numeric, billdiscount numeric, othercharge numeric, taxableamt numeric, totaltax numeric, roundoff numeric, totalamount numeric, otherchargeledgerid character varying, lrno character varying, transportcompany character varying, branchid integer, createddate timestamp without time zone, createduser character varying) OWNER TO postgres;

--
-- TOC entry 343 (class 1255 OID 27530)
-- Name: insert_sales_return_details1(integer, integer, character varying, text, numeric, numeric, numeric, integer, numeric, integer, character varying, numeric, numeric, numeric, numeric, numeric, character varying, numeric, numeric, numeric, integer, character varying, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_sales_return_details1(returnmasterid integer, salesdetails1id integer, productcode character varying, productdescription text, qty numeric, freeqty numeric, rate numeric, unitid integer, discountpercentage numeric, taxid integer, taxtype character varying, taxamount numeric, grossamount numeric, netamount numeric, amount numeric, conversionfactor numeric, barcode character varying, purchaserate numeric, billdisconproduct numeric, addcostonproduct numeric, godownid integer, rackid character varying, branchid integer, createduser character varying) RETURNS TABLE(out_returndetails1id bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO public."tbl_SalesReturnDetails1" (
        "ReturnMasterId",
        "salesDetails1Id",
        "productCode",
        "productDescription",
        qty,
        "freeQty",
        rate,
        "unitId",
        "discountPercentage",
        "taxId",
        "taxType",
        "taxAmount",
        "grossAmount",
        "netAmount",
        amount,
        "ConversionFactor",
        barcode,
        "PurchaseRate",
        "billDiscOnProduct",
        "AddCostonProduct",
        "GodownId",
        "RackId",
        "branchId",
        "CreatedDate",
        "CreatedUser"
    )
    VALUES (
        ReturnMasterId,
        salesDetails1Id,
        productCode,
        productDescription,
        qty,
        freeQty,
        rate,
        unitId,
        discountPercentage,
        taxId,
        taxType,
        taxAmount,
        grossAmount,
        netAmount,
        amount,
        ConversionFactor,
        barcode,
        PurchaseRate,
        billDiscOnProduct,
        AddCostonProduct,
        GodownId,
        RackId,
        branchId,
        NOW(),
        CreatedUser
    )
    RETURNING "ReturnDetails1Id" INTO out_ReturnDetails1Id;

    RETURN QUERY
    SELECT out_ReturnDetails1Id, 'Sales return detail inserted successfully!'::text;
END;
$$;


ALTER FUNCTION public.insert_sales_return_details1(returnmasterid integer, salesdetails1id integer, productcode character varying, productdescription text, qty numeric, freeqty numeric, rate numeric, unitid integer, discountpercentage numeric, taxid integer, taxtype character varying, taxamount numeric, grossamount numeric, netamount numeric, amount numeric, conversionfactor numeric, barcode character varying, purchaserate numeric, billdisconproduct numeric, addcostonproduct numeric, godownid integer, rackid character varying, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 327 (class 1255 OID 45639)
-- Name: insert_sales_return_master(bigint, character varying, integer, character varying, timestamp without time zone, character varying, integer, integer, integer, integer, integer, integer, character varying, integer, numeric, timestamp without time zone, character varying, text, character varying, character varying, character varying, timestamp without time zone, character varying, character varying, text, character varying, numeric, numeric, numeric, numeric, numeric, numeric, numeric, character varying, integer, character varying, numeric, integer, character varying, numeric, numeric, boolean, boolean, character varying, character varying, timestamp without time zone, character varying, boolean, character varying, character varying, character varying, character varying, character varying, character varying, character varying, integer, character varying, text, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_sales_return_master(returnmasterid bigint, voucherno character varying, suffixprefixid integer, returnno character varying, date timestamp without time zone, billtime character varying, salesmasterid integer, ledgerid integer, salesaccountid integer, pricinglevelid integer, employeeid integer, costcentreid integer, batchid character varying, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, partyname character varying, partyaddress text, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, lrno character varying, transportcompany character varying, narration text, taxtype character varying, taxableamt numeric, subtotal numeric, totaltax numeric, additionalcost numeric, billdiscount numeric, roundoff numeric, totalamount numeric, paymentmode character varying, cashledgerid integer, cashrefno character varying, cashamount numeric, bankledgerid integer, bankrefno character varying, bankamount numeric, billbalanceamount numeric, billdisconproductstatus boolean, addcostonproductstatus boolean, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, againstno character varying, isvan boolean, vanexecutiveid character varying, preinvoicehash character varying, currentinvoicehash character varying, uuid character varying, status character varying, logdata character varying, qr_link character varying, branchid integer, createduser character varying, reason text, godownid integer, creditperiod integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO public."tbl_SalesReturnMaster" (
        "ReturnMasterId", "voucherNo", "suffixPrefixId", "returnNo", "date", "billTime",
        "salesMasterId", "ledgerId", "salesAccountId", "pricingLevelId", "employeeId",
        "costCentreId", "BatchId", "currencyConversionId", "exchangeRate", "exchangeDate",
        "partyName", "partyAddress", "partyMobile", "partyVatNo", "partyRefNo", "partyRefDate",
        "lrNo", "transportCompany", "narration", "taxType", "taxableAmt", "subTotal",
        "totalTax", "additionalCost", "billDiscount", "roundOff", "totalAmount",
        "paymentMode", "CashLedgerId", "CashRefNo", "CashAmount",
        "BankLedgerId", "BankRefNo", "BankAmount", "BillBalanceAmount",
        "billDiscOnProductStatus", "AddCostonProductStatus",
        "postedStatus", "postedBy", "postedDate", "AgainstNo", "isVan", "vanExecutiveId",
        "preinvoiceHash", "currentinvoiceHash", "UUID", "status", "logdata", "qr_link",
        "branchId", "CreatedDate", "CreatedUser", "reason", "GodownId", "creditPeriod"
    ) VALUES (
        ReturnMasterId, voucherNo, suffixPrefixId, returnNo, date, billTime,
        salesMasterId, ledgerId, salesAccountId, pricingLevelId, employeeId,
        costCentreId, BatchId, currencyConversionId, exchangeRate, exchangeDate,
        partyName, partyAddress, partyMobile, partyVatNo, partyRefNo, partyRefDate,
        lrNo, transportCompany, narration, taxType, taxableAmt, subTotal,
        totalTax, additionalCost, billDiscount, roundOff, totalAmount,
        paymentMode, CashLedgerId, CashRefNo, CashAmount,
        BankLedgerId, BankRefNo, BankAmount, BillBalanceAmount,
        billDiscOnProductStatus, AddCostonProductStatus,
        postedStatus, postedBy, postedDate, AgainstNo, isVan, vanExecutiveId,
        preinvoiceHash, currentinvoiceHash, UUID, status, logdata, qr_link,
        branchId, NOW(), CreatedUser, reason, GodownId, creditPeriod
    );

    RETURN ReturnMasterId;
END;
$$;


ALTER FUNCTION public.insert_sales_return_master(returnmasterid bigint, voucherno character varying, suffixprefixid integer, returnno character varying, date timestamp without time zone, billtime character varying, salesmasterid integer, ledgerid integer, salesaccountid integer, pricinglevelid integer, employeeid integer, costcentreid integer, batchid character varying, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, partyname character varying, partyaddress text, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, lrno character varying, transportcompany character varying, narration text, taxtype character varying, taxableamt numeric, subtotal numeric, totaltax numeric, additionalcost numeric, billdiscount numeric, roundoff numeric, totalamount numeric, paymentmode character varying, cashledgerid integer, cashrefno character varying, cashamount numeric, bankledgerid integer, bankrefno character varying, bankamount numeric, billbalanceamount numeric, billdisconproductstatus boolean, addcostonproductstatus boolean, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, againstno character varying, isvan boolean, vanexecutiveid character varying, preinvoicehash character varying, currentinvoicehash character varying, uuid character varying, status character varying, logdata character varying, qr_link character varying, branchid integer, createduser character varying, reason text, godownid integer, creditperiod integer) OWNER TO postgres;

--
-- TOC entry 333 (class 1255 OID 27390)
-- Name: insert_stock_posting(timestamp without time zone, character varying, character varying, character varying, integer, integer, integer, numeric, integer, numeric, numeric, numeric, numeric, integer, character varying, character varying, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.insert_stock_posting(date timestamp without time zone, vouchertype character varying, voucherno character varying, productcode character varying, batchid integer, godownid integer, rackid integer, voucherqty numeric, voucherunitid integer, voucherrate numeric, inwardqty numeric, outwardqty numeric, rate numeric, unitid integer, referenceno character varying, referencetype character varying, branchid integer, createduser character varying) RETURNS TABLE(out_stockpostingid integer, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO "tbl_StockPosting" (
        date,
        "voucherType",
        "voucherNo",
        "productCode",
        "batchId",
        "godownId",
        "rackId",
        "VoucherQty",
        "VoucherUnitId",
        "voucherRate",
        "inwardQty",
        "outwardQty",
        rate,
        "unitId",
        "referenceNo",
        "referenceType",
        "branchId",
        "CreatedDate",
        "CreatedUser"
    )
    VALUES (
        date,
        voucherType,
        voucherNo,
        productCode,
        batchId,
        godownId,
        rackId,
        VoucherQty,
        VoucherUnitId,
        voucherRate,
        inwardQty,
        outwardQty,
        rate,
        unitId,
        referenceNo,
        referenceType,
        branchId,
        NOW(),
        CreatedUser
    )
    RETURNING "stockPostingId" INTO out_stockPostingId;

    RETURN QUERY
    SELECT out_stockPostingId, 'Stock posting inserted successfully!'::text;
END;
$$;


ALTER FUNCTION public.insert_stock_posting(date timestamp without time zone, vouchertype character varying, voucherno character varying, productcode character varying, batchid integer, godownid integer, rackid integer, voucherqty numeric, voucherunitid integer, voucherrate numeric, inwardqty numeric, outwardqty numeric, rate numeric, unitid integer, referenceno character varying, referencetype character varying, branchid integer, createduser character varying) OWNER TO postgres;

--
-- TOC entry 304 (class 1255 OID 27289)
-- Name: update_area(bigint, character varying, text, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_area(areaid bigint, areaname character varying, narration text, modifieduser character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_exists INT;
BEGIN
    -- Check if area exists
    IF NOT EXISTS (SELECT 1 FROM "tbl_Area" WHERE "AreaId" = AreaId) THEN
        RAISE EXCEPTION 'Area not found';
    END IF;

    -- Check if new AreaName already exists for another record (case-insensitive)
    SELECT COUNT(*) INTO v_exists
    FROM "tbl_Area"
    WHERE LOWER("AreaName") = LOWER(AreaName)
      AND "AreaId" != AreaId;

    IF v_exists > 0 THEN
        RAISE EXCEPTION 'Area Name Already Exists !';
    END IF;

    -- Update area
    UPDATE "tbl_Area"
    SET
        "AreaName" = AreaName,
        "Narration" = Narration,
        "ModifiedDate" = NOW(),
        "ModifiedUser" = ModifiedUser
    WHERE "AreaId" = AreaId;
END;
$$;


ALTER FUNCTION public.update_area(areaid bigint, areaname character varying, narration text, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 394 (class 1255 OID 62220)
-- Name: update_delivery_note_master(bigint, timestamp without time zone, integer, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, integer, integer, integer, text, integer, numeric, timestamp without time zone, character varying, timestamp without time zone, character varying, integer, integer, integer, character varying, character varying, numeric, numeric, numeric, numeric, numeric, numeric, numeric, integer, numeric, numeric, character varying, character varying, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, integer, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_delivery_note_master(deliverynotemasterid bigint, date timestamp without time zone, ledgerid integer, customername character varying, customeraddress character varying, customerphone character varying, customervatno character varying, refno character varying, refdate timestamp without time zone, costcentreid integer, pricinglevelid integer, employeeid integer, narration text, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, lpono character varying, lpodate timestamp without time zone, againstno character varying, ordermasterid integer, quotationmasterid integer, proformamasterid integer, batchid character varying, taxtype character varying, totalamount numeric, taxableamt numeric, additionalcost numeric, totaltax numeric, billdiscount numeric, roundoff numeric, othercharge numeric, otherchargeledgerid integer, taxtotal numeric, grandtotal numeric, transportcompany character varying, deliverylocation character varying, drivername character varying, contactno character varying, deliveryvehicleno character varying, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, branchid integer, modifieddate timestamp without time zone, modifieduser character varying) RETURNS TABLE(out_deliverynotemasterid bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Perform update
    UPDATE public."tbl_DeliveryNoteMaster"
    SET
        date = update_delivery_note_master.date,
        "ledgerId" = ledgerid,
        "customerName" = customername,
        "CustomerAddress" = customeraddress,
        "CustomerPhone" = customerphone,
        "CustomerVATNo" = customervatno,
        "RefNo" = refno,
        "RefDate" = refdate,
        "costCentreId" = costcentreid,
        "pricingLevelId" = pricinglevelid,
        "employeeId" = employeeid,
        narration = update_delivery_note_master.narration,
        "currencyConversionId" = currencyconversionid,
        "exchangeRate" = exchangerate,
        "exchangeDate" = exchangedate,
        "LPONo" = lpono,
        "LPODate" = lpodate,
        "AgainstNo" = againstno,
        "orderMasterId" = ordermasterid,
        "quotationMasterId" = quotationmasterid,
        "proformaMasterId" = proformamasterid,
        "BatchId" = batchid,
        "taxType" = taxtype,
        "totalAmount" = totalamount,
        "taxableAmt" = taxableamt,
        "additionalCost" = additionalcost,
        "totalTax" = totaltax,
        "billDiscount" = billdiscount,
        "roundOff" = roundoff,
        othercharge = update_delivery_note_master.othercharge,
        "otherChargeLedgerId" = otherchargeledgerid,
        "taxTotal" = taxtotal,
        "grandTotal" = grandtotal,
        "transportCompany" = transportcompany,
        "deliveryLocation" = deliverylocation,
        "driverName" = drivername,
        "contactNo" = contactno,
        "deliveryVehicleNo" = deliveryvehicleno,
        "postedStatus" = postedstatus,
        "postedBy" = postedby,
        "postedDate" = posteddate,
        "branchId" = branchid,
        "ModifiedDate" = modifieddate,
        "ModifiedUser" = modifieduser
    WHERE "deliveryNoteMasterId" = deliverynotemasterid;

    IF FOUND THEN
        RETURN QUERY SELECT deliverynotemasterid, 'Delivery Note updated successfully'::text;
    ELSE
        RETURN QUERY SELECT NULL::bigint, 'Delivery Note not found or not updated'::text;
    END IF;
END;
$$;


ALTER FUNCTION public.update_delivery_note_master(deliverynotemasterid bigint, date timestamp without time zone, ledgerid integer, customername character varying, customeraddress character varying, customerphone character varying, customervatno character varying, refno character varying, refdate timestamp without time zone, costcentreid integer, pricinglevelid integer, employeeid integer, narration text, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, lpono character varying, lpodate timestamp without time zone, againstno character varying, ordermasterid integer, quotationmasterid integer, proformamasterid integer, batchid character varying, taxtype character varying, totalamount numeric, taxableamt numeric, additionalcost numeric, totaltax numeric, billdiscount numeric, roundoff numeric, othercharge numeric, otherchargeledgerid integer, taxtotal numeric, grandtotal numeric, transportcompany character varying, deliverylocation character varying, drivername character varying, contactno character varying, deliveryvehicleno character varying, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, branchid integer, modifieddate timestamp without time zone, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 392 (class 1255 OID 37699)
-- Name: update_proforma_invoice_master(bigint, timestamp without time zone, timestamp without time zone, boolean, integer, character varying, text, character varying, character varying, character varying, timestamp without time zone, character varying, character varying, integer, character varying, integer, integer, text, integer, numeric, timestamp without time zone, integer, character varying, character varying, timestamp without time zone, timestamp without time zone, character varying, character varying, character varying, character varying, numeric, numeric, numeric, numeric, character varying, numeric, numeric, numeric, numeric, numeric, integer, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_proforma_invoice_master(proformamasterid bigint, date timestamp without time zone, duedate timestamp without time zone, cancelled boolean, ledgerid integer, partyname character varying, partyaddress text, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, status character varying, taxtype character varying, quotationmasterid integer, againstno character varying, pricinglevelid integer, employeeid integer, narration text, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, costcentreid integer, batchid character varying, lpono character varying, lpodate timestamp without time zone, deliverydate timestamp without time zone, deliveryterms character varying, paymentterms character varying, lrno character varying, transportcompany character varying, subtotal numeric, additionalcost numeric, billdiscount numeric, othercharge numeric, otherchargeledgerid character varying, taxableamt numeric, totaltax numeric, roundoff numeric, totalamount numeric, advanceperc numeric, branchid integer, modifieddate timestamp without time zone, modifieduser character varying) RETURNS TABLE("out_proformaMasterId" bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE public."tbl_proformaInvoiceMaster"
    SET
        date = update_proforma_invoice_master.date,
        "dueDate" = dueDate,
        cancelled = update_proforma_invoice_master.cancelled,
        "ledgerId" = ledgerId,
        "partyName" = partyName,
        "partyAddress" = partyAddress,
        "partyMobile" = partyMobile,
        "partyVatNo" = partyVatNo,
        "partyRefNo" = partyRefNo,
        "partyRefDate" = partyRefDate,
        status = update_proforma_invoice_master.status,
        "taxType" = taxType,
        "quotationMasterId" = quotationMasterId,
        "AgainstNo" = AgainstNo,
        "pricingLevelId" = pricingLevelId,
        "employeeId" = employeeId,
        narration = update_proforma_invoice_master.narration,
        "currencyConversionId" = currencyConversionId,
        "exchangeRate" = exchangeRate,
        "exchangeDate" = exchangeDate,
        "costCentreId" = costCentreId,
        "BatchId" = BatchId,
        "LPONO" = LPONO,
        "LPODate" = LPODate,
        "deliveryDate" = deliveryDate,
        "DeliveryTerms" = DeliveryTerms,
        "PaymentTerms" = PaymentTerms,
        "lrNo" = lrNo,
        "transportCompany" = transportCompany,
        "subTotal" = subTotal,
        "additionalCost" = additionalCost,
        "billDiscount" = billDiscount,
        othercharge = update_proforma_invoice_master.othercharge,
        "otherChargeLedgerId" = otherChargeLedgerId,
        "taxableAmt" = taxableAmt,
        "totalTax" = totalTax,
        "roundOff" = roundOff,
        "totalAmount" = totalAmount,
        "AdvancePerc" = AdvancePerc,
        "branchId" = branchId,
        "ModifiedDate" = ModifiedDate,
        "ModifiedUser" = ModifiedUser
    WHERE "proformaMasterId" = proformaMasterId;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Proforma Master with ID % not found', proformaMasterId;
    END IF;

    RETURN QUERY
    SELECT proformaMasterId, 'Proforma Master updated successfully'::TEXT;
END;
$$;


ALTER FUNCTION public.update_proforma_invoice_master(proformamasterid bigint, date timestamp without time zone, duedate timestamp without time zone, cancelled boolean, ledgerid integer, partyname character varying, partyaddress text, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, status character varying, taxtype character varying, quotationmasterid integer, againstno character varying, pricinglevelid integer, employeeid integer, narration text, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, costcentreid integer, batchid character varying, lpono character varying, lpodate timestamp without time zone, deliverydate timestamp without time zone, deliveryterms character varying, paymentterms character varying, lrno character varying, transportcompany character varying, subtotal numeric, additionalcost numeric, billdiscount numeric, othercharge numeric, otherchargeledgerid character varying, taxableamt numeric, totaltax numeric, roundoff numeric, totalamount numeric, advanceperc numeric, branchid integer, modifieddate timestamp without time zone, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 398 (class 1255 OID 37343)
-- Name: update_quotation_master(bigint, integer, integer, timestamp without time zone, integer, numeric, timestamp without time zone, character varying, integer, character varying, text, character varying, character varying, integer, integer, text, character varying, timestamp without time zone, character varying, numeric, numeric, numeric, character varying, numeric, numeric, numeric, numeric, numeric, boolean, boolean, boolean, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, timestamp without time zone, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_quotation_master(quotationmasterid bigint, employeeid integer, pricinglevelid integer, date timestamp without time zone, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, batchid character varying, ledgerid integer, customername character varying, customeraddress text, customerphone character varying, customervatno character varying, creditperiod integer, costcentreid integer, narration text, partyrefno character varying, partyrefdate timestamp without time zone, taxtype character varying, totalamount numeric, billdiscount numeric, additionalcost numeric, otherchargeledgerid character varying, othercharge numeric, taxableamt numeric, totaltax numeric, roundoff numeric, grandtotal numeric, billdisconproductstatus boolean, addcostonproductstatus boolean, approved boolean, contactno character varying, contactperson character varying, deliveredwithin character varying, deliverysite character varying, paymentterms character varying, quatationvalidity character varying, deliveryterms character varying, certificate character varying, incoterms character varying, email character varying, carmake character varying, carmodel character varying, kilometer character varying, nextservice character varying, vehicleintime timestamp without time zone, vehicleouttime timestamp without time zone, branchid integer, modifieduser character varying) RETURNS TABLE("out_quotationMasterId" bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE public."tbl_QuotationMaster"
    SET
        "employeeId" = employeeId,
        "pricingLevelId" = pricingLevelId,
        date = update_quotation_master.date,
        "currencyConversionId" = currencyConversionId,
        "exchangeRate" = exchangeRate,
        "exchangeDate" = exchangeDate,
        "BatchId" = BatchId,
        "ledgerId" = ledgerId,
        "customerName" = customerName,
        "CustomerAddress" = CustomerAddress,
        "CustomerPhone" = CustomerPhone,
        "CustomerVatNo" = CustomerVatNo,
        "creditPeriod" = creditPeriod,
        "costCentreId" = costCentreId,
        narration = update_quotation_master.narration,
        "partyRefNo" = partyRefNo,
        "partyRefDate" = partyRefDate,
        "taxType" = taxType,
        "totalAmount" = totalAmount,
        "billDiscount" = billDiscount,
        "additionalCost" = additionalCost,
        "otherChargeLedgerId" = otherChargeLedgerId,
        othercharge = update_quotation_master.othercharge,
        "taxableAmt" = taxableAmt,
        "totalTax" = totalTax,
        "roundOff" = roundOff,
        "grandTotal" = grandTotal,
        "billDiscOnProductStatus" = billDiscOnProductStatus,
        "AddCostonProductStatus" = AddCostonProductStatus,
        approved = update_quotation_master.approved,
        contactno = update_quotation_master.contactno,
        contactperson = update_quotation_master.contactperson,
        deliveredwithin = update_quotation_master.deliveredwithin,
        deliverysite = update_quotation_master.deliverysite,
        paymentterms = update_quotation_master.paymentterms,
        quatationvalidity = update_quotation_master.quatationvalidity,
        "DeliveryTerms" = DeliveryTerms,
        "Certificate" = Certificate,
        "IncoTerms" = IncoTerms,
        "Email" = Email,
        "CarMake" = CarMake,
        "CarModel" = CarModel,
        "Kilometer" = Kilometer,
        "NextService" = NextService,
        "VehicleInTime" = VehicleInTime,
        "VehicleOutTime" = VehicleOutTime,
        "branchId" = branchId,
        "ModifiedDate" = NOW(),
        "ModifiedUser" = ModifiedUser
    WHERE "quotationMasterId" = quotationMasterId;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Quotation Master with ID % not found', quotationMasterId;
    END IF;

    RETURN QUERY
    SELECT quotationMasterId, 'Quotation Master updated successfully'::TEXT;
END;
$$;


ALTER FUNCTION public.update_quotation_master(quotationmasterid bigint, employeeid integer, pricinglevelid integer, date timestamp without time zone, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, batchid character varying, ledgerid integer, customername character varying, customeraddress text, customerphone character varying, customervatno character varying, creditperiod integer, costcentreid integer, narration text, partyrefno character varying, partyrefdate timestamp without time zone, taxtype character varying, totalamount numeric, billdiscount numeric, additionalcost numeric, otherchargeledgerid character varying, othercharge numeric, taxableamt numeric, totaltax numeric, roundoff numeric, grandtotal numeric, billdisconproductstatus boolean, addcostonproductstatus boolean, approved boolean, contactno character varying, contactperson character varying, deliveredwithin character varying, deliverysite character varying, paymentterms character varying, quatationvalidity character varying, deliveryterms character varying, certificate character varying, incoterms character varying, email character varying, carmake character varying, carmodel character varying, kilometer character varying, nextservice character varying, vehicleintime timestamp without time zone, vehicleouttime timestamp without time zone, branchid integer, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 371 (class 1255 OID 27519)
-- Name: update_receipt_master(bigint, timestamp without time zone, integer, text, numeric, character varying, integer, integer, character varying, timestamp without time zone, character varying, character varying, timestamp without time zone, character varying, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_receipt_master(receiptmasterid bigint, date timestamp without time zone, ledgerid integer, narration text, totalamount numeric, userid character varying, employeeid integer, costcentreid integer, referenceno character varying, referencedate timestamp without time zone, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, vanexecutiveid character varying, branchid integer, modifieduser character varying) RETURNS TABLE(updated_id bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE public."tbl_ReceiptMaster"
    SET
        date = update_receipt_master.date,
        "ledgerId" = ledgerId,
        narration = update_receipt_master.narration,
        "totalAmount" = totalAmount,
        "userId" = userId,
        "employeeId" = employeeId,
        "costCentreId" = costCentreId,
        "ReferenceNo" = referenceNo,
        "ReferenceDate" = referenceDate,
        "postedStatus" = postedStatus,
        "postedBy" = postedBy,
        "postedDate" = postedDate,
        "vanExecutiveId" = vanExecutiveId,
        "branchId" = branchId,
        "ModifiedUser" = modifiedUser,
        "ModifiedDate" = (NOW() AT TIME ZONE 'Asia/Kolkata')
    WHERE "receiptMasterId" = receiptMasterId;

    IF FOUND THEN
        RETURN QUERY SELECT receiptMasterId, 'Receipt Master updated successfully';
    ELSE
        RETURN QUERY SELECT NULL::bigint, 'No record found for update';
    END IF;
END;
$$;


ALTER FUNCTION public.update_receipt_master(receiptmasterid bigint, date timestamp without time zone, ledgerid integer, narration text, totalamount numeric, userid character varying, employeeid integer, costcentreid integer, referenceno character varying, referencedate timestamp without time zone, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, vanexecutiveid character varying, branchid integer, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 355 (class 1255 OID 27440)
-- Name: update_sales_master(bigint, timestamp without time zone, character varying, integer, integer, integer, integer, integer, character varying, integer, integer, integer, character varying, text, character varying, character varying, character varying, timestamp without time zone, character varying, timestamp without time zone, integer, timestamp without time zone, timestamp without time zone, numeric, timestamp without time zone, integer, integer, integer, integer, character varying, character varying, character varying, character varying, text, numeric, numeric, numeric, numeric, character varying, integer, numeric, numeric, numeric, numeric, numeric, numeric, integer, character varying, timestamp without time zone, numeric, numeric, character varying, integer, character varying, numeric, integer, character varying, numeric, numeric, character varying, character varying, character varying, character varying, timestamp without time zone, timestamp without time zone, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, boolean, boolean, integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_sales_master(salesmasterid bigint, date timestamp without time zone, billtime character varying, ledgerid integer, pricinglevelid integer, employeeid integer, salesaccount integer, currencyconversionid integer, taxtype character varying, godownid integer, costcentreid integer, batchid integer, customername character varying, customeraddress text, customerphone character varying, customervatno character varying, refno character varying, refdate timestamp without time zone, orderrefno character varying, orderrefdate timestamp without time zone, creditperiod integer, duedate timestamp without time zone, deliverydate timestamp without time zone, exchangerate numeric, exchangedate timestamp without time zone, deliverynotemasterid integer, ordermasterid integer, quotationmasterid integer, proformamasterid integer, againstno character varying, lrno character varying, transportcompany character varying, vehicleno character varying, narration text, taxableamt numeric, subtotal numeric, totaltax numeric, additionalcost numeric, otherchargeremark character varying, otherchargeledgerid integer, othercharge numeric, billdiscount numeric, roundoff numeric, totalamount numeric, adjustmentamount numeric, advanceperc numeric, retentionledgerid integer, retentiontype character varying, retentionduedate timestamp without time zone, retentionpercentage numeric, retentionamount numeric, paymentmode character varying, cashledgerid integer, cashrefno character varying, cashamount numeric, bankledgerid integer, bankrefno character varying, bankamount numeric, billbalanceamount numeric, carmake character varying, carmodel character varying, kilometer character varying, nextservice character varying, vehicleintime timestamp without time zone, vehicleouttime timestamp without time zone, preinvoicehash character varying, currentinvoicehash character varying, uuid character varying, zatcastatus character varying, logdata character varying, qr_link character varying, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, billdisconproductstatus boolean, addcostonproductstatus boolean, branchid integer, modifieduser character varying) RETURNS TABLE(out_salesmasterid bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Check if salesMaster exists
    IF NOT EXISTS (SELECT 1 FROM "tbl_SalesMaster" WHERE "salesMasterId" = salesMasterId) THEN
        RETURN QUERY SELECT NULL::BIGINT, 'Sales Master Not Found!'::TEXT;
        RETURN;
    END IF;

    -- Update record
    UPDATE "tbl_SalesMaster"
    SET
        "date" = update_sales_master.date,
        "billTime" = billTime,
        "ledgerId" = ledgerId,
        "pricingLevelId" = pricingLevelId,
        "employeeId" = employeeId,
        "salesAccount" = salesAccount,
        "currencyConversionId" = currencyConversionId,
        "taxType" = taxType,
        "GodownId" = GodownId,
        "costCentreId" = costCentreId,
        "BatchId" = BatchId,
        "customerName" = customerName,
        "CustomerAddress" = CustomerAddress,
        "CustomerPhone" = CustomerPhone,
        "customerVATNo" = customerVATNo,
        "RefNo" = RefNo,
        "refDate" = refDate,
        "orderRefNo" = orderRefNo,
        "orderRefDate" = orderRefDate,
        "creditPeriod" = creditPeriod,
        "dueDate" = dueDate,
        "deliveryDate" = deliveryDate,
        "exchangeRate" = exchangeRate,
        "exchangeDate" = exchangeDate,
        "deliveryNoteMasterId" = deliveryNoteMasterId,
        "orderMasterId" = orderMasterId,
        "quotationMasterId" = quotationMasterId,
        "proformaMasterId" = proformaMasterId,
        "AgainstNo" = AgainstNo,
        "lrNo" = lrNo,
        "transportCompany" = transportCompany,
        "vehicleNo" = vehicleNo,
        "narration" = update_sales_master.narration,
        "taxableAmt" = taxableAmt,
        "subTotal" = subTotal,
        "totalTax" = totalTax,
        "additionalCost" = additionalCost,
        "OtherChargeRemark" = OtherChargeRemark,
        "otherChargeLedgerId" = otherChargeLedgerId,
        "othercharge" = update_sales_master.othercharge,
        "billDiscount" = billDiscount,
        "roundOff" = roundOff,
        "totalAmount" = totalAmount,
        "AdjustmentAmount" = AdjustmentAmount,
        "AdvancePerc" = AdvancePerc,
        "RetentionLedgerId" = RetentionLedgerId,
        "RetentionType" = RetentionType,
        "RetentionDueDate" = RetentionDueDate,
        "RetentionPercentage" = RetentionPercentage,
        "RetentionAmount" = RetentionAmount,
        "paymentMode" = paymentMode,
        "CashLedgerId" = CashLedgerId,
        "CashRefNo" = CashRefNo,
        "CashAmount" = CashAmount,
        "BankLedgerId" = BankLedgerId,
        "BankRefNo" = BankRefNo,
        "BankAmount" = BankAmount,
        "BillBalanceAmount" = BillBalanceAmount,
        "CarMake" = CarMake,
        "CarModel" = CarModel,
        "Kilometer" = Kilometer,
        "NextService" = NextService,
        "VehicleInTime" = VehicleInTime,
        "VehicleOutTime" = VehicleOutTime,
        "preinvoiceHash" = preinvoiceHash,
        "currentinvoiceHash" = currentinvoiceHash,
        "UUID" = UUID,
        "Zatcastatus" = Zatcastatus,
        "logdata" = update_sales_master.logdata,
        "qr_link" = update_sales_master.qr_link,
        "postedStatus" = postedStatus,
        "postedBy" = postedBy,
        "postedDate" = postedDate,
        "billDiscOnProductStatus" = billDiscOnProductStatus,
        "AddCostonProductStatus" = AddCostonProductStatus,
        "branchId" = branchId,
        "ModifiedDate" = NOW(),
        "ModifiedUser" = ModifiedUser
    WHERE "salesMasterId" = salesMasterId;

    RETURN QUERY SELECT salesMasterId, 'Sales Master Updated Successfully!'::TEXT;
END;
$$;


ALTER FUNCTION public.update_sales_master(salesmasterid bigint, date timestamp without time zone, billtime character varying, ledgerid integer, pricinglevelid integer, employeeid integer, salesaccount integer, currencyconversionid integer, taxtype character varying, godownid integer, costcentreid integer, batchid integer, customername character varying, customeraddress text, customerphone character varying, customervatno character varying, refno character varying, refdate timestamp without time zone, orderrefno character varying, orderrefdate timestamp without time zone, creditperiod integer, duedate timestamp without time zone, deliverydate timestamp without time zone, exchangerate numeric, exchangedate timestamp without time zone, deliverynotemasterid integer, ordermasterid integer, quotationmasterid integer, proformamasterid integer, againstno character varying, lrno character varying, transportcompany character varying, vehicleno character varying, narration text, taxableamt numeric, subtotal numeric, totaltax numeric, additionalcost numeric, otherchargeremark character varying, otherchargeledgerid integer, othercharge numeric, billdiscount numeric, roundoff numeric, totalamount numeric, adjustmentamount numeric, advanceperc numeric, retentionledgerid integer, retentiontype character varying, retentionduedate timestamp without time zone, retentionpercentage numeric, retentionamount numeric, paymentmode character varying, cashledgerid integer, cashrefno character varying, cashamount numeric, bankledgerid integer, bankrefno character varying, bankamount numeric, billbalanceamount numeric, carmake character varying, carmodel character varying, kilometer character varying, nextservice character varying, vehicleintime timestamp without time zone, vehicleouttime timestamp without time zone, preinvoicehash character varying, currentinvoicehash character varying, uuid character varying, zatcastatus character varying, logdata character varying, qr_link character varying, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, billdisconproductstatus boolean, addcostonproductstatus boolean, branchid integer, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 405 (class 1255 OID 54059)
-- Name: update_sales_order_master(bigint, timestamp without time zone, timestamp without time zone, integer, character varying, character varying, character varying, character varying, character varying, timestamp without time zone, integer, integer, text, integer, integer, numeric, timestamp without time zone, character varying, integer, integer, character varying, timestamp without time zone, character varying, character varying, numeric, numeric, numeric, numeric, numeric, numeric, numeric, numeric, character varying, character varying, character varying, integer, timestamp without time zone, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_sales_order_master(ordermasterid bigint, date timestamp without time zone, duedate timestamp without time zone, ledgerid integer, partyname character varying, partyaddress character varying, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, pricinglevelid integer, employeeid integer, narration text, costcentreid integer, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, againstno character varying, quotationmasterid integer, proformamasterid integer, lpono character varying, lpodate timestamp without time zone, status character varying, taxtype character varying, subtotal numeric, additionalcost numeric, billdiscount numeric, othercharge numeric, taxableamt numeric, totaltax numeric, roundoff numeric, totalamount numeric, otherchargeledgerid character varying, lrno character varying, transportcompany character varying, branchid integer, modifieddate timestamp without time zone, modifieduser character varying) RETURNS TABLE(out_ordermasterid bigint, message text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE public."tbl_SalesOrderMaster"
    SET 
        date = update_sales_order_master.date,
        "dueDate" = dueDate,
        "ledgerId" = ledgerId,
        "partyName" = partyName,
        "partyAddress" = partyAddress,
        "partyMobile" = partyMobile,
        "partyVatNo" = partyVatNo,
        "partyRefNo" = partyRefNo,
        "partyRefDate" = partyRefDate,
        "pricingLevelId" = pricingLevelId,
        "employeeId" = employeeId,
        narration = update_sales_order_master.narration,
        "costCentreId" = costCentreId,
        "currencyConversionId" = currencyConversionId,
        "exchangeRate" = exchangeRate,
        "exchangeDate" = exchangeDate,
        "AgainstNo" = AgainstNo,
        "quotationMasterId" = quotationMasterId,
        "proformaMasterId" = proformaMasterId,
        "LPONO" = LPONO,
        "LPODate" = LPODate,
        status = update_sales_order_master.status,
        "taxType" = taxType,
        "subTotal" = subTotal,
        "additionalCost" = additionalCost,
        "billDiscount" = billDiscount,
        othercharge = update_sales_order_master.othercharge,
        "taxableAmt" = taxableAmt,
        "totalTax" = totalTax,
        "roundOff" = roundOff,
        "totalAmount" = totalAmount,
        "otherChargeLedgerId" = otherChargeLedgerId,
        "lrNo" = lrNo,
        "transportCompany" = transportCompany,
        "branchId" = branchId,
        "ModifiedDate" = ModifiedDate,
        "ModifiedUser" = ModifiedUser
    WHERE "orderMasterId" = orderMasterId;

    RETURN QUERY
    SELECT orderMasterId, 'Sales Order Master updated successfully'::text;
END;
$$;


ALTER FUNCTION public.update_sales_order_master(ordermasterid bigint, date timestamp without time zone, duedate timestamp without time zone, ledgerid integer, partyname character varying, partyaddress character varying, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, pricinglevelid integer, employeeid integer, narration text, costcentreid integer, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, againstno character varying, quotationmasterid integer, proformamasterid integer, lpono character varying, lpodate timestamp without time zone, status character varying, taxtype character varying, subtotal numeric, additionalcost numeric, billdiscount numeric, othercharge numeric, taxableamt numeric, totaltax numeric, roundoff numeric, totalamount numeric, otherchargeledgerid character varying, lrno character varying, transportcompany character varying, branchid integer, modifieddate timestamp without time zone, modifieduser character varying) OWNER TO postgres;

--
-- TOC entry 366 (class 1255 OID 45642)
-- Name: update_sales_return_master(bigint, timestamp without time zone, character varying, integer, integer, integer, integer, integer, integer, character varying, integer, numeric, timestamp without time zone, character varying, text, character varying, character varying, character varying, timestamp without time zone, character varying, character varying, text, character varying, numeric, numeric, numeric, numeric, numeric, numeric, numeric, character varying, integer, character varying, numeric, integer, character varying, numeric, numeric, boolean, boolean, character varying, character varying, timestamp without time zone, character varying, boolean, character varying, character varying, character varying, character varying, character varying, character varying, character varying, integer, character varying, text, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_sales_return_master(returnmasterid bigint, date timestamp without time zone, billtime character varying, salesmasterid integer, ledgerid integer, salesaccountid integer, pricinglevelid integer, employeeid integer, costcentreid integer, batchid character varying, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, partyname character varying, partyaddress text, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, lrno character varying, transportcompany character varying, narration text, taxtype character varying, taxableamt numeric, subtotal numeric, totaltax numeric, additionalcost numeric, billdiscount numeric, roundoff numeric, totalamount numeric, paymentmode character varying, cashledgerid integer, cashrefno character varying, cashamount numeric, bankledgerid integer, bankrefno character varying, bankamount numeric, billbalanceamount numeric, billdisconproductstatus boolean, addcostonproductstatus boolean, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, againstno character varying, isvan boolean, vanexecutiveid character varying, preinvoicehash character varying, currentinvoicehash character varying, uuid character varying, status character varying, logdata character varying, qr_link character varying, branchid integer, modifieduser character varying, reason text, godownid integer, creditperiod integer) RETURNS text
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE public."tbl_SalesReturnMaster"
    SET
        date = update_sales_return_master.date,
        "billTime" = billTime,
        "salesMasterId" = salesMasterId,
        "ledgerId" = ledgerId,
        "salesAccountId" = salesAccountId,
        "pricingLevelId" = pricingLevelId,
        "employeeId" = employeeId,
        "costCentreId" = costCentreId,
        "BatchId" = BatchId,
        "currencyConversionId" = currencyConversionId,
        "exchangeRate" = exchangeRate,
        "exchangeDate" = exchangeDate,
        "partyName" = partyName,
        "partyAddress" = partyAddress,
        "partyMobile" = partyMobile,
        "partyVatNo" = partyVatNo,
        "partyRefNo" = partyRefNo,
        "partyRefDate" = partyRefDate,
        "lrNo" = lrNo,
        "transportCompany" = transportCompany,
        narration = update_sales_return_master.narration,
        "taxType" = taxType,
        "taxableAmt" = taxableAmt,
        "subTotal" = subTotal,
        "totalTax" = totalTax,
        "additionalCost" = additionalCost,
        "billDiscount" = billDiscount,
        "roundOff" = roundOff,
        "totalAmount" = totalAmount,
        "paymentMode" = paymentMode,
        "CashLedgerId" = CashLedgerId,
        "CashRefNo" = CashRefNo,
        "CashAmount" = CashAmount,
        "BankLedgerId" = BankLedgerId,
        "BankRefNo" = BankRefNo,
        "BankAmount" = BankAmount,
        "BillBalanceAmount" = BillBalanceAmount,
        "billDiscOnProductStatus" = billDiscOnProductStatus,
        "AddCostonProductStatus" = AddCostonProductStatus,
        "postedStatus" = postedStatus,
        "postedBy" = postedBy,
        "postedDate" = postedDate,
        "AgainstNo" = AgainstNo,
        "isVan" = isVan,
        "vanExecutiveId" = vanExecutiveId,
        "preinvoiceHash" = preinvoiceHash,
        "currentinvoiceHash" = currentinvoiceHash,
        "UUID" = UUID,
        status = update_sales_return_master.status,
        logdata = update_sales_return_master.logdata,
        qr_link = update_sales_return_master.qr_link,
        "branchId" = branchId,
        "ModifiedDate" = NOW(),
        "ModifiedUser" = ModifiedUser,
		"reason" = update_sales_return_master.reason,
        "GodownId" = update_sales_return_master.GodownId,
        "creditPeriod" = update_sales_return_master.creditPeriod

    WHERE "ReturnMasterId" = ReturnMasterId;

    RETURN 'Sales return updated successfully!';
END;
$$;


ALTER FUNCTION public.update_sales_return_master(returnmasterid bigint, date timestamp without time zone, billtime character varying, salesmasterid integer, ledgerid integer, salesaccountid integer, pricinglevelid integer, employeeid integer, costcentreid integer, batchid character varying, currencyconversionid integer, exchangerate numeric, exchangedate timestamp without time zone, partyname character varying, partyaddress text, partymobile character varying, partyvatno character varying, partyrefno character varying, partyrefdate timestamp without time zone, lrno character varying, transportcompany character varying, narration text, taxtype character varying, taxableamt numeric, subtotal numeric, totaltax numeric, additionalcost numeric, billdiscount numeric, roundoff numeric, totalamount numeric, paymentmode character varying, cashledgerid integer, cashrefno character varying, cashamount numeric, bankledgerid integer, bankrefno character varying, bankamount numeric, billbalanceamount numeric, billdisconproductstatus boolean, addcostonproductstatus boolean, postedstatus character varying, postedby character varying, posteddate timestamp without time zone, againstno character varying, isvan boolean, vanexecutiveid character varying, preinvoicehash character varying, currentinvoicehash character varying, uuid character varying, status character varying, logdata character varying, qr_link character varying, branchid integer, modifieduser character varying, reason text, godownid integer, creditperiod integer) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 217 (class 1259 OID 16729)
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- TOC entry 218 (class 1259 OID 16734)
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- TOC entry 301 (class 1259 OID 62133)
-- Name: decimalpoint; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.decimalpoint (
    "noOfDecimalPlace" integer
);


ALTER TABLE public.decimalpoint OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16739)
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 16745)
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- TOC entry 5635 (class 0 OID 0)
-- Dependencies: 220
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- TOC entry 221 (class 1259 OID 16746)
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 16751)
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16756)
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- TOC entry 5636 (class 0 OID 0)
-- Dependencies: 223
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- TOC entry 224 (class 1259 OID 16757)
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 16760)
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- TOC entry 5637 (class 0 OID 0)
-- Dependencies: 225
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- TOC entry 226 (class 1259 OID 16761)
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 16766)
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 16771)
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.personal_access_tokens_id_seq OWNER TO postgres;

--
-- TOC entry 5638 (class 0 OID 0)
-- Dependencies: 228
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- TOC entry 229 (class 1259 OID 16772)
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 16801)
-- Name: tbl_AccountGroup; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_AccountGroup" (
    "groupId" bigint NOT NULL,
    "AccountGroupCode" character varying(255),
    "accountGroupName" character varying(255),
    "groupUnder" integer,
    narration text,
    "LedgerNextNo" integer,
    "branchId" character varying(100),
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "default" boolean
);


ALTER TABLE public."tbl_AccountGroup" OWNER TO postgres;

--
-- TOC entry 255 (class 1259 OID 17403)
-- Name: tbl_AccountLedger; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_AccountLedger" (
    "ledgerId" bigint NOT NULL,
    "ledgerName" character varying(255),
    "groupId" integer,
    "billBybill" boolean,
    "openingBalance" numeric(18,2),
    "crOrDr" character varying(255),
    narration text,
    "nameArb" character varying(255),
    "accountNo" character varying(255),
    address text,
    "phoneNo" character varying(100),
    "faxNo" character varying(200),
    email character varying(200),
    "creditPeriod" integer,
    "creditLimit" numeric(18,2),
    "pricingLevelId" integer,
    "currencyId" integer,
    "interestOrNot" boolean,
    "branchId" integer,
    "marketId" integer,
    "default" boolean,
    "tinNumber" character varying(255),
    "cstNumber" character varying(255),
    "panNumber" character varying(255),
    "extraDate" timestamp without time zone,
    extra1 character varying(255),
    extra2 character varying(255),
    "areaId" integer,
    "ledgerCode" character varying(100),
    "BuildingNo" character varying(200),
    "AdditionalNo" character varying(200),
    "StreetName" character varying(255),
    "PostboxNo" character varying(255),
    "CityName" character varying(255),
    "Country" character varying(255),
    "creditLimitStatus" character varying(255),
    bankaccname character varying(255),
    bankname character varying(255),
    ibanno character varying(255),
    "District" character varying(200),
    "StreetNameArb" character varying(255),
    "BuildingNoArb" character varying(255),
    "CityNameArb" character varying(255),
    "DistrictArb" character varying(255),
    "CountryArb" character varying(255),
    "AdditionalNoArb" character varying(255),
    "PostboxNoArb" character varying(255),
    "bankBranchName" character varying(255),
    "bankSwiftCode" character varying(255),
    "AddressArabic" character varying(255),
    "ledgerType" character varying(100),
    "routeId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_AccountLedger" OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 16807)
-- Name: tbl_Area; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Area" (
    "AreaId" bigint NOT NULL,
    "AreaName" character varying(255),
    "Narration" text,
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_Area" OWNER TO postgres;

--
-- TOC entry 264 (class 1259 OID 18073)
-- Name: tbl_BOM; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_BOM" (
    "bomId" bigint NOT NULL,
    "productCode" character varying,
    "rowMaterialId" character varying,
    "UnitId" integer,
    "Quantity" numeric(18,2),
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_BOM" OWNER TO postgres;

--
-- TOC entry 263 (class 1259 OID 18072)
-- Name: tbl_BOM_bomId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_BOM" ALTER COLUMN "bomId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_BOM_bomId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 230 (class 1259 OID 16777)
-- Name: tbl_Brand; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Brand" (
    "brandId" bigint NOT NULL,
    "brandName" character varying(255),
    "Narration" text,
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_Brand" OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 16827)
-- Name: tbl_CostCentre; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_CostCentre" (
    "costCentreId" bigint NOT NULL,
    "CostCentre" character varying(255),
    "Narration" text,
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_CostCentre" OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 16833)
-- Name: tbl_Currency; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Currency" (
    "currencyId" bigint NOT NULL,
    "currencySymbol" character varying(255),
    "currencyName" character varying(255),
    "subunitName" character varying(255),
    "noOfDecimalPlace" integer,
    narration text,
    "default" boolean,
    "branchId" character varying(100),
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_Currency" OWNER TO postgres;

--
-- TOC entry 265 (class 1259 OID 18107)
-- Name: tbl_CurrencyConversion; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_CurrencyConversion" (
    "currencyConversionId" bigint NOT NULL,
    "currencyId" integer,
    date timestamp without time zone,
    rate numeric(24,8),
    narration text,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_CurrencyConversion" OWNER TO postgres;

--
-- TOC entry 300 (class 1259 OID 54424)
-- Name: tbl_DeliveryNoteDetails1; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_DeliveryNoteDetails1" (
    "deliveryNoteDetails1Id" bigint NOT NULL,
    "deliveryNoteMasterId" integer,
    "productCode" character varying(50),
    "SlNo" integer,
    qty numeric(18,3),
    "freeQty" numeric(18,3),
    rate numeric(18,6),
    "unitId" integer,
    amount numeric(18,6),
    "ConversionFactor" numeric(18,2),
    barcode text,
    "discountPercentage" numeric(18,2),
    "taxAmount" numeric(18,6),
    "grossAmount" numeric(18,6),
    "taxType" character varying(255),
    "netAmount" numeric(18,6),
    "billDiscOnProduct" numeric(18,10),
    "AddCostonProduct" numeric(18,10),
    "productDescription" character varying(255),
    "PurchaseRate" numeric(18,6),
    otherchargeonproduct numeric(18,6),
    "Remark" text,
    "taxId" integer,
    "GodownId" integer,
    "RackId" character varying(50),
    "orderDetails1Id" integer,
    "quotationDetailsId" integer,
    "proformaDetails1Id" integer,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_DeliveryNoteDetails1" OWNER TO postgres;

--
-- TOC entry 299 (class 1259 OID 54355)
-- Name: tbl_DeliveryNoteMaster; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_DeliveryNoteMaster" (
    "deliveryNoteMasterId" bigint NOT NULL,
    "suffixPrefixId" integer,
    "voucherNo" character varying(50),
    "deliveryNoteNo" character varying(50),
    date timestamp without time zone,
    "ledgerId" integer,
    "customerName" character varying(255),
    "CustomerAddress" character varying(255),
    "CustomerPhone" character varying(255),
    "CustomerVATNo" character varying(255),
    "RefNo" character varying(255),
    "RefDate" timestamp without time zone,
    "costCentreId" integer,
    "pricingLevelId" integer,
    "employeeId" integer,
    narration text,
    "currencyConversionId" integer,
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "LPONo" character varying(255),
    "LPODate" timestamp without time zone,
    "AgainstNo" character varying(50),
    "orderMasterId" integer,
    "quotationMasterId" integer,
    "proformaMasterId" integer,
    "BatchId" character varying(50),
    "taxType" character varying(255),
    "totalAmount" numeric(18,6),
    "taxableAmt" numeric(18,6),
    "additionalCost" numeric(18,6),
    "totalTax" numeric(18,6),
    "billDiscount" numeric(18,6),
    "roundOff" numeric(18,6),
    othercharge numeric(18,6),
    "otherChargeLedgerId" integer,
    "taxTotal" numeric(18,6),
    "grandTotal" numeric(18,6),
    "transportCompany" character varying(255),
    "deliveryLocation" character varying(255),
    "driverName" character varying(255),
    "contactNo" character varying(255),
    "deliveryVehicleNo" character varying(255),
    "postedStatus" character varying(50),
    "postedBy" character varying(50),
    "postedDate" timestamp without time zone,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_DeliveryNoteMaster" OWNER TO postgres;

--
-- TOC entry 274 (class 1259 OID 26210)
-- Name: tbl_Department; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Department" (
    "departmentId" bigint NOT NULL,
    "departmentName" character varying(255),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_Department" OWNER TO postgres;

--
-- TOC entry 273 (class 1259 OID 18362)
-- Name: tbl_Designation; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Designation" (
    "designationId" integer NOT NULL,
    "designationName" character varying(255),
    "leaveDays" integer,
    "advancePercentage" numeric(18,2),
    narration text,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_Designation" OWNER TO postgres;

--
-- TOC entry 276 (class 1259 OID 26262)
-- Name: tbl_Employee; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Employee" (
    "employeeId" bigint NOT NULL,
    "suffixPrefixId" integer,
    "voucherNo" character varying(50),
    "employeeCode" character varying(50),
    "employeeName" character varying(255),
    "designationId" integer,
    "DepartmentId" integer,
    "workLocationId" integer,
    dob timestamp without time zone,
    "meritalStatus" character varying(255),
    gender character varying(255),
    qualification character varying(255),
    "PermenentAddress" text,
    "CurrentAddress" text,
    "Nationality" text,
    "whatsAppNumber" character varying(255),
    "phoneNo" character varying(255),
    "mobileNo" character varying(255),
    email character varying(255),
    "emergencyContactName" character varying(255),
    "emergencyContactNumber" character varying(255),
    "joiningDate" timestamp without time zone,
    "terminationDate" timestamp without time zone,
    active boolean,
    narration text,
    "bloodGroup" character varying(50),
    "routeId" integer,
    "areaId" integer,
    "marketId" integer,
    "passportNo" character varying(255),
    "passportIssueDate" timestamp without time zone,
    "passportExpDate" timestamp without time zone,
    "visaType" character varying(255),
    "labourCardNo" character varying(255),
    "labourCardIssueDate" timestamp without time zone,
    "labourCardExpDate" timestamp without time zone,
    "visaNo" character varying(255),
    "visaExpDate" timestamp without time zone,
    "OTHourlyAmount" numeric(18,2),
    "InsuranceNumber" character varying(255),
    "InsuranceExpirydate" timestamp without time zone,
    "MOLContractNumber" character varying(255),
    "SponsorName" character varying(255),
    "GOSI_SSOnumber" character varying(255),
    "WPScompliance" character varying(255),
    "BankName" character varying(255),
    "BankAccountNo" character varying(255),
    "IBAN" character varying(255),
    "SwiftCode" character varying(255),
    "BankBranch" character varying(255),
    employeephoto character varying(255),
    "documentuploadOptionNeeded" boolean,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_Employee" OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 16839)
-- Name: tbl_FinanceSettings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_FinanceSettings" (
    "FinanceSettingsId" bigint NOT NULL,
    "ShowAllTransactions" boolean,
    "MaintainBillbyBill" boolean,
    "multiCurrency" boolean,
    "showLedgerbalance" boolean,
    "createdDate" timestamp without time zone,
    "createdUser" character varying(100),
    "modifiedDate" timestamp without time zone,
    "modifiedUser" character varying(100),
    "CloseAfterSave" boolean,
    "branchId" integer
);


ALTER TABLE public."tbl_FinanceSettings" OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 16842)
-- Name: tbl_FinanceSettings_FinanceSettingsId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."tbl_FinanceSettings_FinanceSettingsId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."tbl_FinanceSettings_FinanceSettingsId_seq" OWNER TO postgres;

--
-- TOC entry 5639 (class 0 OID 0)
-- Dependencies: 239
-- Name: tbl_FinanceSettings_FinanceSettingsId_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."tbl_FinanceSettings_FinanceSettingsId_seq" OWNED BY public."tbl_FinanceSettings"."FinanceSettingsId";


--
-- TOC entry 260 (class 1259 OID 17971)
-- Name: tbl_FinancialYear; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_FinancialYear" (
    "yearId" bigint NOT NULL,
    "fromDate" timestamp without time zone,
    "toDate" timestamp without time zone,
    closed boolean,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_FinancialYear" OWNER TO postgres;

--
-- TOC entry 262 (class 1259 OID 17981)
-- Name: tbl_FinancialYearMonth; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_FinancialYearMonth" (
    "monthId" bigint NOT NULL,
    "yearId" integer,
    "MonthName" character varying(255),
    closed boolean,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_FinancialYearMonth" OWNER TO postgres;

--
-- TOC entry 261 (class 1259 OID 17980)
-- Name: tbl_FinancialYearMonth_monthId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_FinancialYearMonth" ALTER COLUMN "monthId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_FinancialYearMonth_monthId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 240 (class 1259 OID 16843)
-- Name: tbl_GeneralSettings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_GeneralSettings" (
    "generalSettingsId" bigint NOT NULL,
    "branchId" character varying(255),
    "ActivateTax" boolean,
    "costCentre" boolean,
    "AccountPosting" boolean,
    "negativeCashTransaction" character varying(255),
    "AccountCalculationMethod" character varying(255),
    "decimalPart" integer,
    dateformat character varying(255),
    "taxType" character varying(255),
    taxincluded boolean,
    "taxCessincluded" boolean,
    "branchHeader" character varying(255),
    "branchFooter" character varying(255),
    "zatcaType" character varying(255),
    "RoundOff" integer,
    "RoundOffDigit" character varying(255),
    "showCurrencyprefix" boolean,
    "askConfirmationSave" boolean,
    "askConfirmationEdit" boolean,
    "askConfirmationDelete" boolean,
    "askConfirmationClear" boolean,
    "askConfirmationRowRemove" boolean,
    "askConfirmationPrint" boolean,
    "askConfirmationClose" boolean,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(200),
    "ModifedDate" timestamp without time zone,
    "ModifedUser" character varying(100)
);


ALTER TABLE public."tbl_GeneralSettings" OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 16848)
-- Name: tbl_GeneralSettings_generalSettingsId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."tbl_GeneralSettings_generalSettingsId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."tbl_GeneralSettings_generalSettingsId_seq" OWNER TO postgres;

--
-- TOC entry 5640 (class 0 OID 0)
-- Dependencies: 241
-- Name: tbl_GeneralSettings_generalSettingsId_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."tbl_GeneralSettings_generalSettingsId_seq" OWNED BY public."tbl_GeneralSettings"."generalSettingsId";


--
-- TOC entry 231 (class 1259 OID 16783)
-- Name: tbl_Godown; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Godown" (
    "GodownId" bigint NOT NULL,
    "GodownName" character varying(255),
    "Narration" text,
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_Godown" OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 16849)
-- Name: tbl_InventorySettings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_InventorySettings" (
    "InventorySettingsId" bigint NOT NULL,
    "NegativeStock" character varying(255),
    "stockValueCalculation" character varying(255),
    "SalesPriceUpdateByCostPricePercentage" boolean,
    "maintainGodown" boolean,
    "maintainRack" boolean,
    "lowStockReminder" boolean,
    "CloseAfterSave" boolean,
    "branchId" integer,
    "CreatedDate" timestamp with time zone,
    "CreatedUser" character varying(100)
);


ALTER TABLE public."tbl_InventorySettings" OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 16854)
-- Name: tbl_InventorySettings_InventorySettingsId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."tbl_InventorySettings_InventorySettingsId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."tbl_InventorySettings_InventorySettingsId_seq" OWNER TO postgres;

--
-- TOC entry 5641 (class 0 OID 0)
-- Dependencies: 243
-- Name: tbl_InventorySettings_InventorySettingsId_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."tbl_InventorySettings_InventorySettingsId_seq" OWNED BY public."tbl_InventorySettings"."InventorySettingsId";


--
-- TOC entry 278 (class 1259 OID 26384)
-- Name: tbl_LedgerPosting; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_LedgerPosting" (
    "ledgerPostingId" bigint NOT NULL,
    date timestamp without time zone,
    "voucherType" character varying(50),
    "voucherNo" integer,
    "ledgerId" integer,
    debit numeric(18,6),
    credit numeric(18,6),
    "InvoiceNo" character varying(50),
    "Narration" text,
    "costCentreId" integer,
    "PostingType" character varying(50),
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100)
);


ALTER TABLE public."tbl_LedgerPosting" OWNER TO postgres;

--
-- TOC entry 277 (class 1259 OID 26383)
-- Name: tbl_LedgerPosting_ledgerPostingId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_LedgerPosting" ALTER COLUMN "ledgerPostingId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_LedgerPosting_ledgerPostingId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 244 (class 1259 OID 16855)
-- Name: tbl_Market; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Market" (
    "MarketId" bigint NOT NULL,
    "MarketName" character varying(255),
    "Narration" text,
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_Market" OWNER TO postgres;

--
-- TOC entry 282 (class 1259 OID 26515)
-- Name: tbl_Partybalance; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Partybalance" (
    "balanceId" bigint NOT NULL,
    date timestamp without time zone,
    "ledgerId" integer,
    "voucherType" character varying(50),
    "voucherNo" integer,
    "againstVoucherType" character varying(50),
    "againstvoucherNo" character varying(50),
    "referenceType" character varying(255),
    debit numeric(18,2),
    credit numeric(18,2),
    "creditPeriod" integer,
    "currencyConversionId" integer,
    "invoiceNo" character varying(50),
    "referenceNo" character varying(50),
    "BillAmount" numeric(18,6),
    "invoiceDate" timestamp without time zone,
    "costCentreId" integer,
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100)
);


ALTER TABLE public."tbl_Partybalance" OWNER TO postgres;

--
-- TOC entry 281 (class 1259 OID 26514)
-- Name: tbl_Partybalance_balanceId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_Partybalance" ALTER COLUMN "balanceId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_Partybalance_balanceId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 232 (class 1259 OID 16789)
-- Name: tbl_PricingLevel; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_PricingLevel" (
    "PricingLevelId" bigint NOT NULL,
    "PricingLevelName" character varying(255),
    "Narration" text,
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_PricingLevel" OWNER TO postgres;

--
-- TOC entry 290 (class 1259 OID 27298)
-- Name: tbl_Product; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Product" (
    "productCode" character varying(255) NOT NULL,
    "productName" character varying(255),
    "group1Id" integer,
    "brandId" integer,
    "unitId" integer,
    "allowBatch" boolean,
    "partNo" character varying(50),
    "productImage" character varying(255),
    "purchaseRate" numeric(18,2),
    "taxId" integer,
    "minimumStock" numeric(18,2),
    "maximumStock" numeric(18,2),
    "reorderLevel" numeric(18,2),
    "openingStock" numeric(18,2),
    "taxType" character varying(255),
    narration text,
    bom boolean,
    active boolean,
    "showReminder" boolean,
    "fixedSalesRate" numeric(18,6),
    "avgCost" numeric(18,6),
    "fifoValue" numeric(18,6),
    "avgRate" numeric(18,6),
    "fifoRate" numeric(18,6),
    category character varying(255),
    "groupCode" character varying(255),
    "ShowExpiry" boolean,
    "ExpiryDays" integer,
    "PurchaseRatePer" numeric(18,6),
    "Ingredients" text,
    "NutritionFact" boolean,
    "NutritionName" character varying(100),
    "NutritionDetails" text,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(50),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(50),
    "productNameArb" character varying(255),
    "group2Id" integer,
    "group3Id" integer,
    "group4Id" integer,
    "Location" text,
    "AlternativeNo" text
);


ALTER TABLE public."tbl_Product" OWNER TO postgres;

--
-- TOC entry 253 (class 1259 OID 17377)
-- Name: tbl_ProductGroup; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_ProductGroup" (
    "groupId" bigint NOT NULL,
    "groupName" character varying(255),
    category character varying(200),
    narration text,
    "branchId" character varying(100),
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_ProductGroup" OWNER TO postgres;

--
-- TOC entry 252 (class 1259 OID 17363)
-- Name: tbl_ProductMainGroup; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_ProductMainGroup" (
    "groupCode" character varying(255) NOT NULL,
    "groupName" character varying(255),
    "scaleGroup" boolean,
    "productCodeLength" integer,
    "nextNumber" integer,
    "branchId" character varying(100),
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_ProductMainGroup" OWNER TO postgres;

--
-- TOC entry 256 (class 1259 OID 17535)
-- Name: tbl_ProductSalesPrice; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_ProductSalesPrice" (
    "salespriceId" bigint NOT NULL,
    "productCode" character varying(255),
    "unitId" integer,
    amount numeric(18,3),
    "discPercentage" numeric(18,2),
    "discAmount" numeric(18,3),
    "salesPrice" numeric(18,3),
    "branchId" integer,
    "PricingLevelId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_ProductSalesPrice" OWNER TO postgres;

--
-- TOC entry 258 (class 1259 OID 17665)
-- Name: tbl_ProductSalesPrice_salespriceId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_ProductSalesPrice" ALTER COLUMN "salespriceId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_ProductSalesPrice_salespriceId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 291 (class 1259 OID 27303)
-- Name: tbl_Product_productCode_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."tbl_Product_productCode_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."tbl_Product_productCode_seq" OWNER TO postgres;

--
-- TOC entry 5642 (class 0 OID 0)
-- Dependencies: 291
-- Name: tbl_Product_productCode_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."tbl_Product_productCode_seq" OWNED BY public."tbl_Product"."productCode";


--
-- TOC entry 245 (class 1259 OID 16861)
-- Name: tbl_PurchaseSettings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_PurchaseSettings" (
    "PurchaseSettingsId" bigint NOT NULL,
    "VendorInvoiceNoChecking" boolean,
    "showProductDescription" boolean,
    "showFeeQtyColumn" boolean,
    "ActivatePurchaseRetention" boolean,
    "ChangeSalesPrice" boolean,
    "showLineDiscount" boolean,
    "CloseAfterSave" boolean,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100)
);


ALTER TABLE public."tbl_PurchaseSettings" OWNER TO postgres;

--
-- TOC entry 246 (class 1259 OID 16864)
-- Name: tbl_PurchaseSettings_PurchaseSettingsId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."tbl_PurchaseSettings_PurchaseSettingsId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."tbl_PurchaseSettings_PurchaseSettingsId_seq" OWNER TO postgres;

--
-- TOC entry 5643 (class 0 OID 0)
-- Dependencies: 246
-- Name: tbl_PurchaseSettings_PurchaseSettingsId_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."tbl_PurchaseSettings_PurchaseSettingsId_seq" OWNED BY public."tbl_PurchaseSettings"."PurchaseSettingsId";


--
-- TOC entry 294 (class 1259 OID 34533)
-- Name: tbl_QuotationDetails; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_QuotationDetails" (
    "quotationDetailsId" bigint NOT NULL,
    "quotationMasterId" integer,
    "productCode" character varying(50),
    "unitId" integer,
    "taxId" integer,
    qty numeric(18,3),
    "freeQty" numeric(18,3),
    rate numeric(18,6),
    amount numeric(18,6),
    "discountPercentage" numeric(18,2),
    "discountAmount" numeric(18,6),
    "taxAmount" numeric(18,6),
    "grossAmount" numeric(18,6),
    "taxType" character varying(255),
    "netAmount" numeric(18,6),
    "ConversionFactor" numeric(18,2),
    barcode character varying(255),
    "billDiscOnProduct" numeric(18,10),
    "AddCostonProduct" numeric(18,10),
    "productDescription" character varying(255),
    "SlNo" integer,
    "PurchaseRate" numeric(18,6),
    otherchargeonproduct numeric(18,6),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_QuotationDetails" OWNER TO postgres;

--
-- TOC entry 293 (class 1259 OID 34532)
-- Name: tbl_QuotationDetails_quotationDetailsId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_QuotationDetails" ALTER COLUMN "quotationDetailsId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_QuotationDetails_quotationDetailsId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 292 (class 1259 OID 34444)
-- Name: tbl_QuotationMaster; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_QuotationMaster" (
    "quotationMasterId" bigint NOT NULL,
    "voucherNo" character varying(50),
    "quotationNo" character varying(50),
    "suffixPrefixId" integer,
    "employeeId" integer,
    "pricingLevelId" integer,
    date timestamp without time zone,
    "currencyConversionId" integer,
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "BatchId" character varying(50),
    "ledgerId" integer,
    "customerName" character varying(255),
    "CustomerAddress" text,
    "CustomerPhone" character varying(255),
    "CustomerVatNo" character varying(255),
    "creditPeriod" integer,
    "costCentreId" integer,
    narration text,
    "partyRefNo" character varying(255),
    "partyRefDate" timestamp without time zone,
    "taxType" character varying(255),
    "totalAmount" numeric(18,6),
    "billDiscount" numeric(18,6),
    "additionalCost" numeric(18,6),
    "otherChargeLedgerId" character varying(50),
    othercharge numeric(18,6),
    "taxableAmt" numeric(18,6),
    "totalTax" numeric(18,6),
    "roundOff" numeric(18,6),
    "grandTotal" numeric(18,6),
    "billDiscOnProductStatus" boolean,
    "AddCostonProductStatus" boolean,
    approved boolean,
    contactno character varying(255),
    contactperson character varying(255),
    deliveredwithin character varying(255),
    deliverysite character varying(255),
    paymentterms character varying(255),
    quatationvalidity character varying(255),
    "DeliveryTerms" character varying(255),
    "Certificate" character varying(255),
    "IncoTerms" character varying(255),
    "Email" character varying(255),
    "CarMake" character varying(50),
    "CarModel" character varying(50),
    "Kilometer" character varying(50),
    "NextService" character varying(50),
    "VehicleInTime" timestamp without time zone,
    "VehicleOutTime" timestamp without time zone,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_QuotationMaster" OWNER TO postgres;

--
-- TOC entry 287 (class 1259 OID 26685)
-- Name: tbl_ReceiptMaster; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_ReceiptMaster" (
    "receiptMasterId" bigint NOT NULL,
    "voucherNo" character varying(255),
    "suffixPrefixId" integer,
    "receiptNo" character varying(50),
    date timestamp without time zone,
    "ledgerId" integer,
    narration text,
    "totalAmount" numeric(18,6),
    "userId" character varying(50),
    "employeeId" integer,
    "costCentreId" integer,
    "ReferenceNo" character varying(255),
    "ReferenceDate" timestamp without time zone,
    "postedStatus" character varying(50),
    "postedDate" timestamp without time zone,
    "vanExecutiveId" character varying(50),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100),
    "postedBy" character varying(50)
);


ALTER TABLE public."tbl_ReceiptMaster" OWNER TO postgres;

--
-- TOC entry 286 (class 1259 OID 26684)
-- Name: tbl_ReceiptMaster_receiptMasterId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_ReceiptMaster" ALTER COLUMN "receiptMasterId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_ReceiptMaster_receiptMasterId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 289 (class 1259 OID 26721)
-- Name: tbl_Receiptdetails; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Receiptdetails" (
    "receiptDetailsId" bigint NOT NULL,
    "receiptMasterId" integer,
    "SlNo" integer,
    "ledgerId" integer,
    amount numeric(18,6),
    "Narration" text,
    "chequeNo" character varying(255),
    "chequeDate" timestamp without time zone,
    "currencyConversionId" integer,
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_Receiptdetails" OWNER TO postgres;

--
-- TOC entry 288 (class 1259 OID 26720)
-- Name: tbl_Receiptdetails_receiptDetailsId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_Receiptdetails" ALTER COLUMN "receiptDetailsId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_Receiptdetails_receiptDetailsId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 233 (class 1259 OID 16795)
-- Name: tbl_Route; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Route" (
    "RouteId" bigint NOT NULL,
    "RouteName" character varying(255),
    "Narration" text,
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_Route" OWNER TO postgres;

--
-- TOC entry 270 (class 1259 OID 18192)
-- Name: tbl_SalesDetails1; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_SalesDetails1" (
    "salesDetails1Id" bigint NOT NULL,
    "salesMasterId" integer,
    "deliveryNoteDetails1Id" character varying(50),
    "orderDetails1Id" character varying(50),
    "quotationDetailsId" character varying(50),
    "proformaDetails1Id" character varying(50),
    "SlNo" integer,
    "productCode" character varying(100),
    qty numeric(18,3),
    "freeQty" numeric(18,3),
    rate numeric(18,6),
    "unitId" integer,
    "discountPercentage" numeric(18,2),
    "taxId" integer,
    "taxType" character varying(255),
    "ConversionFactor" numeric(18,2),
    barcode text,
    "PurchaseRate" numeric(18,6),
    "taxAmount" numeric(18,6),
    "grossAmount" numeric(18,6),
    "netAmount" numeric(18,6),
    amount numeric(18,6),
    "productDescription" text,
    "billDiscOnProduct" numeric(18,10),
    "AddCostonProduct" numeric(18,10),
    otherchargeonproduct numeric(18,10),
    "salesManId" integer,
    "GodownId" integer,
    "RackId" integer,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_SalesDetails1" OWNER TO postgres;

--
-- TOC entry 269 (class 1259 OID 18191)
-- Name: tbl_SalesDetails1_salesDetails1Id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_SalesDetails1" ALTER COLUMN "salesDetails1Id" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_SalesDetails1_salesDetails1Id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 268 (class 1259 OID 18149)
-- Name: tbl_SalesMaster; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_SalesMaster" (
    "salesMasterId" bigint NOT NULL,
    "voucherNo" character varying(255),
    "invoiceNo" character varying(255),
    "suffixPrefixId" integer,
    date timestamp without time zone,
    "billTime" character varying(100),
    "ledgerId" integer,
    "pricingLevelId" integer,
    "employeeId" integer,
    "salesAccount" integer,
    "currencyConversionId" integer,
    "taxType" character varying(50),
    "GodownId" integer,
    "costCentreId" integer,
    "BatchId" integer,
    "customerName" character varying(255),
    "CustomerAddress" text,
    "CustomerPhone" character varying(255),
    "customerVATNo" character varying(255),
    "RefNo" character varying(255),
    "refDate" timestamp without time zone,
    "orderRefNo" character varying(255),
    "orderRefDate" timestamp without time zone,
    "creditPeriod" integer,
    "dueDate" timestamp without time zone,
    "deliveryDate" timestamp without time zone,
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "deliveryNoteMasterId" integer,
    "orderMasterId" integer,
    "quotationMasterId" integer,
    "proformaMasterId" integer,
    "AgainstNo" character varying(50),
    "lrNo" character varying(255),
    "transportCompany" character varying(255),
    "vehicleNo" character varying(255),
    narration text,
    "taxableAmt" numeric(18,6),
    "subTotal" numeric(18,6),
    "totalTax" numeric(18,6),
    "additionalCost" numeric(18,6),
    "OtherChargeRemark" character varying(255),
    "otherChargeLedgerId" integer,
    othercharge numeric(18,6),
    "billDiscount" numeric(18,6),
    "roundOff" numeric(18,6),
    "totalAmount" numeric(18,6),
    "AdjustmentAmount" numeric(18,6),
    "AdvancePerc" numeric(18,6),
    "RetentionLedgerId" integer,
    "RetentionType" character varying(50),
    "RetentionDueDate" timestamp without time zone,
    "RetentionPercentage" numeric(18,6),
    "RetentionAmount" numeric(18,6),
    "paymentMode" character varying(50),
    "CashLedgerId" integer,
    "CashRefNo" character varying(255),
    "CashAmount" numeric(18,6),
    "BankLedgerId" integer,
    "BankRefNo" character varying(255),
    "BankAmount" numeric(18,6),
    "BillBalanceAmount" numeric(18,6),
    "CarMake" character varying(50),
    "CarModel" character varying(50),
    "Kilometer" character varying(50),
    "NextService" character varying(50),
    "VehicleInTime" timestamp without time zone,
    "VehicleOutTime" timestamp without time zone,
    "preinvoiceHash" character varying(255),
    "currentinvoiceHash" character varying(255),
    "UUID" character varying(255),
    "Zatcastatus" character varying(255),
    logdata character varying(255),
    qr_link character varying(255),
    "postedStatus" character varying(50),
    "postedBy" character varying(50),
    "postedDate" timestamp without time zone,
    "billDiscOnProductStatus" boolean,
    "AddCostonProductStatus" boolean,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_SalesMaster" OWNER TO postgres;

--
-- TOC entry 297 (class 1259 OID 53944)
-- Name: tbl_SalesOrderMaster; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_SalesOrderMaster" (
    "orderMasterId" bigint NOT NULL,
    "suffixPrefixId" integer,
    "voucherNo" character varying(50),
    "orderNo" character varying(50),
    date timestamp without time zone,
    "dueDate" timestamp without time zone,
    "ledgerId" integer,
    "partyName" character varying(255),
    "partyAddress" character varying(255),
    "partyMobile" character varying(255),
    "partyVatNo" character varying(255),
    "partyRefNo" character varying(255),
    "partyRefDate" timestamp without time zone,
    "pricingLevelId" integer,
    "employeeId" integer,
    narration text,
    "costCentreId" integer,
    "currencyConversionId" integer,
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "AgainstNo" character varying(50),
    "quotationMasterId" integer,
    "proformaMasterId" integer,
    "LPONO" character varying(255),
    "LPODate" timestamp without time zone,
    status character varying(100),
    "taxType" character varying(50),
    "subTotal" numeric(18,6),
    "additionalCost" numeric(18,6),
    "billDiscount" numeric(18,6),
    othercharge numeric(18,6),
    "taxableAmt" numeric(18,6),
    "totalTax" numeric(18,6),
    "roundOff" numeric(18,6),
    "totalAmount" numeric(18,6),
    "otherChargeLedgerId" character varying(50),
    "lrNo" character varying(255),
    "transportCompany" character varying(255),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_SalesOrderMaster" OWNER TO postgres;

--
-- TOC entry 298 (class 1259 OID 53997)
-- Name: tbl_SalesOrderdetails1; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_SalesOrderdetails1" (
    "orderDetails1Id" bigint NOT NULL,
    "orderMasterId" integer,
    "quotationDetailsId" integer,
    "proformaDetails1Id" integer,
    "productCode" character varying(100),
    "productDescription" character varying(255),
    barcode character varying(255),
    qty numeric(18,3),
    "freeQty" numeric(18,3),
    rate numeric(18,6),
    "unitId" integer,
    amount numeric(18,6),
    "ConversionFactor" numeric(18,2),
    "discountPercentage" numeric(18,2),
    "taxId" integer,
    "taxType" character varying(255),
    "taxAmount" numeric(18,6),
    "grossAmount" numeric(18,6),
    "netAmount" numeric(18,6),
    "PurchaseRate" numeric(18,6),
    "billDiscOnProduct" numeric(18,10),
    "AddCostonProduct" numeric(18,10),
    otherchargeonproduct numeric(18,6),
    "SlNo" integer,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_SalesOrderdetails1" OWNER TO postgres;

--
-- TOC entry 285 (class 1259 OID 26617)
-- Name: tbl_SalesReturnDetails1; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_SalesReturnDetails1" (
    "ReturnDetails1Id" bigint NOT NULL,
    "ReturnMasterId" integer,
    "salesDetails1Id" integer,
    "productCode" character varying(50),
    "productDescription" text,
    qty numeric(18,3),
    "freeQty" numeric(18,3),
    rate numeric(18,6),
    "unitId" integer,
    "discountPercentage" numeric(18,2),
    "taxId" integer,
    "taxType" character varying(255),
    "taxAmount" numeric(18,6),
    "grossAmount" numeric(18,6),
    "netAmount" numeric(18,6),
    amount numeric(18,6),
    "ConversionFactor" numeric(18,2),
    barcode character varying(255),
    "PurchaseRate" numeric(18,6),
    "billDiscOnProduct" numeric(18,10),
    "AddCostonProduct" numeric(18,10),
    "GodownId" integer,
    "RackId" character varying(50),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_SalesReturnDetails1" OWNER TO postgres;

--
-- TOC entry 284 (class 1259 OID 26616)
-- Name: tbl_SalesReturnDetails1_ReturnDetails1Id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_SalesReturnDetails1" ALTER COLUMN "ReturnDetails1Id" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_SalesReturnDetails1_ReturnDetails1Id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 283 (class 1259 OID 26570)
-- Name: tbl_SalesReturnMaster; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_SalesReturnMaster" (
    "ReturnMasterId" bigint NOT NULL,
    "voucherNo" character varying(50),
    "suffixPrefixId" integer,
    "returnNo" character varying(50),
    date timestamp without time zone,
    "billTime" character varying(50),
    "salesMasterId" integer,
    "ledgerId" integer,
    "salesAccountId" integer,
    "pricingLevelId" integer,
    "employeeId" integer,
    "costCentreId" integer,
    "BatchId" character varying(50),
    "currencyConversionId" integer,
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "partyName" character varying(255),
    "partyAddress" text,
    "partyMobile" character varying(200),
    "partyVatNo" character varying(255),
    "partyRefNo" character varying(255),
    "partyRefDate" timestamp without time zone,
    "lrNo" character varying(255),
    "transportCompany" character varying(255),
    narration text,
    "taxType" character varying(50),
    "taxableAmt" numeric(18,6),
    "subTotal" numeric(18,6),
    "totalTax" numeric(18,6),
    "additionalCost" numeric(18,6),
    "billDiscount" numeric(18,6),
    "roundOff" numeric(18,6),
    "totalAmount" numeric(18,6),
    "paymentMode" character varying(50),
    "CashLedgerId" integer,
    "CashRefNo" character varying(255),
    "CashAmount" numeric(18,6),
    "BankLedgerId" integer,
    "BankRefNo" character varying(255),
    "BankAmount" numeric(18,6),
    "BillBalanceAmount" numeric(18,6),
    "billDiscOnProductStatus" boolean,
    "AddCostonProductStatus" boolean,
    "postedStatus" character varying(50),
    "postedBy" character varying(50),
    "postedDate" timestamp without time zone,
    "AgainstNo" character varying(50),
    "isVan" boolean,
    "vanExecutiveId" character varying(50),
    "preinvoiceHash" character varying(255),
    "currentinvoiceHash" character varying(255),
    "UUID" character varying(255),
    status character varying(255),
    logdata character varying(255),
    qr_link character varying(255),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(50),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(50),
    reason text,
    "GodownId" integer,
    "creditPeriod" integer
);


ALTER TABLE public."tbl_SalesReturnMaster" OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 16865)
-- Name: tbl_SalesSettings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_SalesSettings" (
    "SalesSettingsId" bigint NOT NULL,
    "showProductDescription" boolean,
    "showFeeQtyColumn" boolean,
    "showLineDiscount" boolean,
    "showPurchaserate" boolean,
    "ShowPartNo" boolean,
    "ActivateSalesRetention" boolean,
    "showBillProfit" boolean,
    "showBillDiscountAmount" boolean,
    "showBillDiscountPerc" boolean,
    "CloseAfterSave" boolean,
    "branchId" integer,
    "CreatedDate" timestamp with time zone,
    "CreatedUser" character varying(100)
);


ALTER TABLE public."tbl_SalesSettings" OWNER TO postgres;

--
-- TOC entry 248 (class 1259 OID 16868)
-- Name: tbl_SalesSettings_SalesSettingsId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public."tbl_SalesSettings_SalesSettingsId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public."tbl_SalesSettings_SalesSettingsId_seq" OWNER TO postgres;

--
-- TOC entry 5644 (class 0 OID 0)
-- Dependencies: 248
-- Name: tbl_SalesSettings_SalesSettingsId_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public."tbl_SalesSettings_SalesSettingsId_seq" OWNED BY public."tbl_SalesSettings"."SalesSettingsId";


--
-- TOC entry 267 (class 1259 OID 18125)
-- Name: tbl_ShippingAddress; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_ShippingAddress" (
    "addressId" bigint NOT NULL,
    "ledgerId" integer,
    address1 text,
    address2 text,
    address3 text,
    address4 text,
    "Isdefault" boolean,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_ShippingAddress" OWNER TO postgres;

--
-- TOC entry 266 (class 1259 OID 18124)
-- Name: tbl_ShippingAddress_addressId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_ShippingAddress" ALTER COLUMN "addressId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_ShippingAddress_addressId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 280 (class 1259 OID 26473)
-- Name: tbl_StockPosting; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_StockPosting" (
    "stockPostingId" integer NOT NULL,
    date timestamp without time zone,
    "voucherType" character varying(50),
    "voucherNo" character varying(100),
    "productCode" character varying(50),
    "batchId" integer,
    "godownId" integer,
    "rackId" integer,
    "VoucherQty" numeric(18,3),
    "VoucherUnitId" integer,
    "voucherRate" numeric(18,6),
    "inwardQty" numeric(18,3),
    "outwardQty" numeric(18,3),
    rate numeric(18,6),
    "unitId" integer,
    "referenceNo" character varying(50),
    "referenceType" character varying(255),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100)
);


ALTER TABLE public."tbl_StockPosting" OWNER TO postgres;

--
-- TOC entry 279 (class 1259 OID 26472)
-- Name: tbl_StockPosting_stockPostingId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_StockPosting" ALTER COLUMN "stockPostingId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_StockPosting_stockPostingId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 272 (class 1259 OID 18298)
-- Name: tbl_SuffixPrefix; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_SuffixPrefix" (
    "suffixPrefixId" bigint NOT NULL,
    "voucherType" character varying(255),
    "yearId" integer,
    suffix character varying(255),
    prefix character varying(255),
    "startIndex" integer,
    "addWithZero" boolean,
    "noOfZero" integer,
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_SuffixPrefix" OWNER TO postgres;

--
-- TOC entry 271 (class 1259 OID 18297)
-- Name: tbl_SuffixPrefix_suffixPrefixId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_SuffixPrefix" ALTER COLUMN "suffixPrefixId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_SuffixPrefix_suffixPrefixId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 254 (class 1259 OID 17388)
-- Name: tbl_TaxMaster; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_TaxMaster" (
    "taxId" bigint NOT NULL,
    "taxName" character varying(255),
    rate numeric,
    "calculatingMode" character varying(255),
    active boolean,
    narration text,
    "branchId" character varying(100),
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_TaxMaster" OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 16869)
-- Name: tbl_Unit; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_Unit" (
    "unitId" bigint NOT NULL,
    "UnitName" character varying(255),
    "Narration" text,
    "CreatedDate" timestamp(0) without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp(0) without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_Unit" OWNER TO postgres;

--
-- TOC entry 257 (class 1259 OID 17558)
-- Name: tbl_UnitConversion; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_UnitConversion" (
    "unitConversionId" bigint NOT NULL,
    "productCode" character varying(255),
    "unitId" integer,
    "conversionRate" numeric(24,2),
    barcode text,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100),
    "branchId" integer
);


ALTER TABLE public."tbl_UnitConversion" OWNER TO postgres;

--
-- TOC entry 259 (class 1259 OID 17666)
-- Name: tbl_UnitConversion_unitConversionId_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public."tbl_UnitConversion" ALTER COLUMN "unitConversionId" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public."tbl_UnitConversion_unitConversionId_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 296 (class 1259 OID 37398)
-- Name: tbl_proformaInvoiceDetails1; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_proformaInvoiceDetails1" (
    "proformaDetails1Id" bigint NOT NULL,
    "proformaMasterId" integer,
    "productCode" character varying(50),
    qty numeric(18,3),
    "freeQty" numeric(18,3),
    rate numeric(18,6),
    "unitId" integer,
    amount numeric(18,6),
    "quotationDetailsId" integer,
    "ConversionFactor" numeric(18,2),
    barcode text,
    "productDescription" text,
    "discountPercentage" numeric(18,2),
    "taxId" integer,
    "taxType" character varying(255),
    "taxAmount" numeric(18,6),
    "grossAmount" numeric(18,6),
    "netAmount" numeric(18,6),
    "PurchaseRate" numeric(18,6),
    "billDiscOnProduct" numeric(18,10),
    "AddCostonProduct" numeric(18,10),
    "SlNo" integer,
    "Size" character varying(255),
    otherchargeonproduct numeric(18,6),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_proformaInvoiceDetails1" OWNER TO postgres;

--
-- TOC entry 295 (class 1259 OID 37359)
-- Name: tbl_proformaInvoiceMaster; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_proformaInvoiceMaster" (
    "proformaMasterId" bigint NOT NULL,
    "suffixPrefixId" integer,
    "voucherNo" character varying(50),
    "proformaNo" character varying(50),
    date timestamp without time zone,
    "dueDate" timestamp without time zone,
    cancelled boolean,
    "ledgerId" integer,
    "partyName" character varying(255),
    "partyAddress" text,
    "partyMobile" character varying(255),
    "partyVatNo" character varying(255),
    "partyRefNo" character varying(255),
    "partyRefDate" timestamp without time zone,
    status character varying(100),
    "taxType" character varying(50),
    "quotationMasterId" integer,
    "AgainstNo" character varying(50),
    "pricingLevelId" integer,
    "employeeId" integer,
    narration text,
    "currencyConversionId" integer,
    "exchangeRate" numeric(18,6),
    "exchangeDate" timestamp without time zone,
    "costCentreId" integer,
    "BatchId" character varying(50),
    "LPONO" character varying(255),
    "LPODate" timestamp without time zone,
    "deliveryDate" timestamp without time zone,
    "DeliveryTerms" character varying(255),
    "PaymentTerms" character varying(255),
    "lrNo" character varying(255),
    "transportCompany" character varying(255),
    "subTotal" numeric(18,6),
    "additionalCost" numeric(18,6),
    "billDiscount" numeric(18,6),
    othercharge numeric(18,6),
    "otherChargeLedgerId" character varying(50),
    "taxableAmt" numeric(18,6),
    "totalTax" numeric(18,6),
    "roundOff" numeric(18,6),
    "totalAmount" numeric(18,6),
    "AdvancePerc" numeric(18,2),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_proformaInvoiceMaster" OWNER TO postgres;

--
-- TOC entry 275 (class 1259 OID 26222)
-- Name: tbl_workLocation; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."tbl_workLocation" (
    "workLocationId" bigint NOT NULL,
    "workLocationName" character varying(255),
    "branchId" integer,
    "CreatedDate" timestamp without time zone,
    "CreatedUser" character varying(100),
    "ModifiedDate" timestamp without time zone,
    "ModifiedUser" character varying(100)
);


ALTER TABLE public."tbl_workLocation" OWNER TO postgres;

--
-- TOC entry 250 (class 1259 OID 16887)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 16892)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 5645 (class 0 OID 0)
-- Dependencies: 251
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 5001 (class 2604 OID 16893)
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- TOC entry 5003 (class 2604 OID 16894)
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- TOC entry 5004 (class 2604 OID 16895)
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- TOC entry 5005 (class 2604 OID 16896)
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- TOC entry 5006 (class 2604 OID 16906)
-- Name: tbl_FinanceSettings FinanceSettingsId; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_FinanceSettings" ALTER COLUMN "FinanceSettingsId" SET DEFAULT nextval('public."tbl_FinanceSettings_FinanceSettingsId_seq"'::regclass);


--
-- TOC entry 5007 (class 2604 OID 16907)
-- Name: tbl_GeneralSettings generalSettingsId; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_GeneralSettings" ALTER COLUMN "generalSettingsId" SET DEFAULT nextval('public."tbl_GeneralSettings_generalSettingsId_seq"'::regclass);


--
-- TOC entry 5008 (class 2604 OID 16908)
-- Name: tbl_InventorySettings InventorySettingsId; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_InventorySettings" ALTER COLUMN "InventorySettingsId" SET DEFAULT nextval('public."tbl_InventorySettings_InventorySettingsId_seq"'::regclass);


--
-- TOC entry 5012 (class 2604 OID 27304)
-- Name: tbl_Product productCode; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product" ALTER COLUMN "productCode" SET DEFAULT nextval('public."tbl_Product_productCode_seq"'::regclass);


--
-- TOC entry 5009 (class 2604 OID 16910)
-- Name: tbl_PurchaseSettings PurchaseSettingsId; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_PurchaseSettings" ALTER COLUMN "PurchaseSettingsId" SET DEFAULT nextval('public."tbl_PurchaseSettings_PurchaseSettingsId_seq"'::regclass);


--
-- TOC entry 5010 (class 2604 OID 16911)
-- Name: tbl_SalesSettings SalesSettingsId; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesSettings" ALTER COLUMN "SalesSettingsId" SET DEFAULT nextval('public."tbl_SalesSettings_SalesSettingsId_seq"'::regclass);


--
-- TOC entry 5011 (class 2604 OID 16915)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5134 (class 2606 OID 17907)
-- Name: tbl_UnitConversion barcode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_UnitConversion"
    ADD CONSTRAINT barcode UNIQUE (barcode);


--
-- TOC entry 5016 (class 2606 OID 16917)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- TOC entry 5014 (class 2606 OID 16919)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- TOC entry 5018 (class 2606 OID 16921)
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5020 (class 2606 OID 16923)
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- TOC entry 5022 (class 2606 OID 16925)
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- TOC entry 5024 (class 2606 OID 16927)
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5123 (class 2606 OID 17901)
-- Name: tbl_AccountLedger ledgerCode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "ledgerCode" UNIQUE ("ledgerCode");


--
-- TOC entry 5125 (class 2606 OID 17412)
-- Name: tbl_AccountLedger ledgerName; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "ledgerName" UNIQUE ("ledgerName");


--
-- TOC entry 5027 (class 2606 OID 16929)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 5029 (class 2606 OID 16931)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- TOC entry 5032 (class 2606 OID 16933)
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- TOC entry 5034 (class 2606 OID 16935)
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- TOC entry 5038 (class 2606 OID 16937)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- TOC entry 5043 (class 2606 OID 16939)
-- Name: tbl_Brand tb_Brand_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Brand"
    ADD CONSTRAINT "tb_Brand_pkey" PRIMARY KEY ("brandId");


--
-- TOC entry 5047 (class 2606 OID 16941)
-- Name: tbl_Godown tb_Godown_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Godown"
    ADD CONSTRAINT "tb_Godown_pkey" PRIMARY KEY ("GodownId");


--
-- TOC entry 5051 (class 2606 OID 16943)
-- Name: tbl_PricingLevel tb_PricingLevel_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_PricingLevel"
    ADD CONSTRAINT "tb_PricingLevel_pkey" PRIMARY KEY ("PricingLevelId");


--
-- TOC entry 5055 (class 2606 OID 16945)
-- Name: tbl_Route tb_Route_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Route"
    ADD CONSTRAINT "tb_Route_pkey" PRIMARY KEY ("RouteId");


--
-- TOC entry 5061 (class 2606 OID 16947)
-- Name: tbl_AccountGroup tbl_AccountGroup_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountGroup"
    ADD CONSTRAINT "tbl_AccountGroup_pkey" PRIMARY KEY ("groupId");


--
-- TOC entry 5127 (class 2606 OID 17756)
-- Name: tbl_AccountLedger tbl_AccountLedger_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "tbl_AccountLedger_pkey" PRIMARY KEY ("ledgerId");


--
-- TOC entry 5065 (class 2606 OID 16949)
-- Name: tbl_Area tbl_Area_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Area"
    ADD CONSTRAINT "tbl_Area_pkey" PRIMARY KEY ("AreaId");


--
-- TOC entry 5158 (class 2606 OID 18079)
-- Name: tbl_BOM tbl_BOM_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_BOM"
    ADD CONSTRAINT "tbl_BOM_pkey" PRIMARY KEY ("bomId");


--
-- TOC entry 5069 (class 2606 OID 16955)
-- Name: tbl_CostCentre tbl_CostCentre_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_CostCentre"
    ADD CONSTRAINT "tbl_CostCentre_pkey" PRIMARY KEY ("costCentreId");


--
-- TOC entry 5162 (class 2606 OID 18113)
-- Name: tbl_CurrencyConversion tbl_CurrencyConversion_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_CurrencyConversion"
    ADD CONSTRAINT "tbl_CurrencyConversion_pkey" PRIMARY KEY ("currencyConversionId");


--
-- TOC entry 5073 (class 2606 OID 16957)
-- Name: tbl_Currency tbl_Currency_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Currency"
    ADD CONSTRAINT "tbl_Currency_pkey" PRIMARY KEY ("currencyId");


--
-- TOC entry 5368 (class 2606 OID 54428)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_pkey" PRIMARY KEY ("deliveryNoteDetails1Id");


--
-- TOC entry 5356 (class 2606 OID 54361)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_pkey" PRIMARY KEY ("deliveryNoteMasterId");


--
-- TOC entry 5202 (class 2606 OID 26214)
-- Name: tbl_Department tbl_Department_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Department"
    ADD CONSTRAINT "tbl_Department_pkey" PRIMARY KEY ("departmentId");


--
-- TOC entry 5198 (class 2606 OID 18368)
-- Name: tbl_Designation tbl_Designation_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Designation"
    ADD CONSTRAINT "tbl_Designation_pkey" PRIMARY KEY ("designationId");


--
-- TOC entry 5218 (class 2606 OID 26268)
-- Name: tbl_Employee tbl_Employee_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Employee"
    ADD CONSTRAINT "tbl_Employee_pkey" PRIMARY KEY ("employeeId");


--
-- TOC entry 5076 (class 2606 OID 16959)
-- Name: tbl_FinanceSettings tbl_FinanceSettings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_FinanceSettings"
    ADD CONSTRAINT "tbl_FinanceSettings_pkey" PRIMARY KEY ("FinanceSettingsId");


--
-- TOC entry 5151 (class 2606 OID 17985)
-- Name: tbl_FinancialYearMonth tbl_FinancialYearMonth_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_FinancialYearMonth"
    ADD CONSTRAINT "tbl_FinancialYearMonth_pkey" PRIMARY KEY ("monthId");


--
-- TOC entry 5144 (class 2606 OID 17975)
-- Name: tbl_FinancialYear tbl_FinancialYear_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_FinancialYear"
    ADD CONSTRAINT "tbl_FinancialYear_pkey" PRIMARY KEY ("yearId");


--
-- TOC entry 5080 (class 2606 OID 16961)
-- Name: tbl_GeneralSettings tbl_GeneralSettings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_GeneralSettings"
    ADD CONSTRAINT "tbl_GeneralSettings_pkey" PRIMARY KEY ("generalSettingsId");


--
-- TOC entry 5083 (class 2606 OID 16963)
-- Name: tbl_InventorySettings tbl_InventorySettings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_InventorySettings"
    ADD CONSTRAINT "tbl_InventorySettings_pkey" PRIMARY KEY ("InventorySettingsId");


--
-- TOC entry 5226 (class 2606 OID 26390)
-- Name: tbl_LedgerPosting tbl_LedgerPosting_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_LedgerPosting"
    ADD CONSTRAINT "tbl_LedgerPosting_pkey" PRIMARY KEY ("ledgerPostingId");


--
-- TOC entry 5087 (class 2606 OID 16965)
-- Name: tbl_Market tbl_Market_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Market"
    ADD CONSTRAINT "tbl_Market_pkey" PRIMARY KEY ("MarketId");


--
-- TOC entry 5243 (class 2606 OID 26521)
-- Name: tbl_Partybalance tbl_Partybalance_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Partybalance"
    ADD CONSTRAINT "tbl_Partybalance_pkey" PRIMARY KEY ("balanceId");


--
-- TOC entry 5109 (class 2606 OID 17383)
-- Name: tbl_ProductGroup tbl_ProductGroup_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ProductGroup"
    ADD CONSTRAINT "tbl_ProductGroup_pkey" PRIMARY KEY ("groupId");


--
-- TOC entry 5105 (class 2606 OID 17615)
-- Name: tbl_ProductMainGroup tbl_ProductMainGroup_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ProductMainGroup"
    ADD CONSTRAINT "tbl_ProductMainGroup_pkey" PRIMARY KEY ("groupCode");


--
-- TOC entry 5132 (class 2606 OID 17539)
-- Name: tbl_ProductSalesPrice tbl_ProductSalesPrice_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ProductSalesPrice"
    ADD CONSTRAINT "tbl_ProductSalesPrice_pkey" PRIMARY KEY ("salespriceId");


--
-- TOC entry 5288 (class 2606 OID 27306)
-- Name: tbl_Product tbl_Product_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product"
    ADD CONSTRAINT "tbl_Product_pkey" PRIMARY KEY ("productCode");


--
-- TOC entry 5090 (class 2606 OID 16967)
-- Name: tbl_PurchaseSettings tbl_PurchaseSettings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_PurchaseSettings"
    ADD CONSTRAINT "tbl_PurchaseSettings_pkey" PRIMARY KEY ("PurchaseSettingsId");


--
-- TOC entry 5305 (class 2606 OID 34539)
-- Name: tbl_QuotationDetails tbl_QuotationDetails_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationDetails"
    ADD CONSTRAINT "tbl_QuotationDetails_pkey" PRIMARY KEY ("quotationDetailsId");


--
-- TOC entry 5298 (class 2606 OID 34450)
-- Name: tbl_QuotationMaster tbl_QuotationMaster_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationMaster"
    ADD CONSTRAINT "tbl_QuotationMaster_pkey" PRIMARY KEY ("quotationMasterId");


--
-- TOC entry 5270 (class 2606 OID 26691)
-- Name: tbl_ReceiptMaster tbl_ReceiptMaster_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ReceiptMaster"
    ADD CONSTRAINT "tbl_ReceiptMaster_pkey" PRIMARY KEY ("receiptMasterId");


--
-- TOC entry 5277 (class 2606 OID 26727)
-- Name: tbl_Receiptdetails tbl_Receiptdetails_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Receiptdetails"
    ADD CONSTRAINT "tbl_Receiptdetails_pkey" PRIMARY KEY ("receiptDetailsId");


--
-- TOC entry 5188 (class 2606 OID 18198)
-- Name: tbl_SalesDetails1 tbl_SalesDetails1_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesDetails1"
    ADD CONSTRAINT "tbl_SalesDetails1_pkey" PRIMARY KEY ("salesDetails1Id");


--
-- TOC entry 5178 (class 2606 OID 18155)
-- Name: tbl_SalesMaster tbl_SalesMaster_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesMaster"
    ADD CONSTRAINT "tbl_SalesMaster_pkey" PRIMARY KEY ("salesMasterId");


--
-- TOC entry 5335 (class 2606 OID 53950)
-- Name: tbl_SalesOrderMaster tbl_SalesOrderMaster_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderMaster"
    ADD CONSTRAINT "tbl_SalesOrderMaster_pkey" PRIMARY KEY ("orderMasterId");


--
-- TOC entry 5344 (class 2606 OID 54003)
-- Name: tbl_SalesOrderdetails1 tbl_SalesOrderdetails1_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderdetails1"
    ADD CONSTRAINT "tbl_SalesOrderdetails1_pkey" PRIMARY KEY ("orderDetails1Id");


--
-- TOC entry 5263 (class 2606 OID 26623)
-- Name: tbl_SalesReturnDetails1 tbl_SalesReturnDetails1_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnDetails1"
    ADD CONSTRAINT "tbl_SalesReturnDetails1_pkey" PRIMARY KEY ("ReturnDetails1Id");


--
-- TOC entry 5254 (class 2606 OID 26576)
-- Name: tbl_SalesReturnMaster tbl_SalesReturnMaster_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnMaster"
    ADD CONSTRAINT "tbl_SalesReturnMaster_pkey" PRIMARY KEY ("ReturnMasterId");


--
-- TOC entry 5093 (class 2606 OID 16969)
-- Name: tbl_SalesSettings tbl_SalesSettings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesSettings"
    ADD CONSTRAINT "tbl_SalesSettings_pkey" PRIMARY KEY ("SalesSettingsId");


--
-- TOC entry 5166 (class 2606 OID 18131)
-- Name: tbl_ShippingAddress tbl_ShippingAddress_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ShippingAddress"
    ADD CONSTRAINT "tbl_ShippingAddress_pkey" PRIMARY KEY ("addressId");


--
-- TOC entry 5235 (class 2606 OID 26479)
-- Name: tbl_StockPosting tbl_StockPosting_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_StockPosting"
    ADD CONSTRAINT "tbl_StockPosting_pkey" PRIMARY KEY ("stockPostingId");


--
-- TOC entry 5194 (class 2606 OID 18304)
-- Name: tbl_SuffixPrefix tbl_SuffixPrefix_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SuffixPrefix"
    ADD CONSTRAINT "tbl_SuffixPrefix_pkey" PRIMARY KEY ("suffixPrefixId");


--
-- TOC entry 5113 (class 2606 OID 17394)
-- Name: tbl_TaxMaster tbl_TaxMaster_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_TaxMaster"
    ADD CONSTRAINT "tbl_TaxMaster_pkey" PRIMARY KEY ("taxId");


--
-- TOC entry 5138 (class 2606 OID 17564)
-- Name: tbl_UnitConversion tbl_UnitConversion_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_UnitConversion"
    ADD CONSTRAINT "tbl_UnitConversion_pkey" PRIMARY KEY ("unitConversionId");


--
-- TOC entry 5097 (class 2606 OID 16971)
-- Name: tbl_Unit tbl_Unit_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Unit"
    ADD CONSTRAINT "tbl_Unit_pkey" PRIMARY KEY ("unitId");


--
-- TOC entry 5323 (class 2606 OID 37404)
-- Name: tbl_proformaInvoiceDetails1 tbl_proformaInvoiceDetails1_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceDetails1"
    ADD CONSTRAINT "tbl_proformaInvoiceDetails1_pkey" PRIMARY KEY ("proformaDetails1Id");


--
-- TOC entry 5315 (class 2606 OID 37365)
-- Name: tbl_proformaInvoiceMaster tbl_proformaInvoiceMaster_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceMaster"
    ADD CONSTRAINT "tbl_proformaInvoiceMaster_pkey" PRIMARY KEY ("proformaMasterId");


--
-- TOC entry 5206 (class 2606 OID 26226)
-- Name: tbl_workLocation tbl_workLocation_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_workLocation"
    ADD CONSTRAINT "tbl_workLocation_pkey" PRIMARY KEY ("workLocationId");


--
-- TOC entry 5099 (class 2606 OID 16977)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- TOC entry 5101 (class 2606 OID 16979)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 5207 (class 1259 OID 26296)
-- Name: fki_Employee_fk_; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Employee_fk_" ON public."tbl_Employee" USING btree ("routeId");


--
-- TOC entry 5208 (class 1259 OID 26283)
-- Name: fki_Employee_fk_DepartmentId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Employee_fk_DepartmentId" ON public."tbl_Employee" USING btree ("DepartmentId");


--
-- TOC entry 5209 (class 1259 OID 26303)
-- Name: fki_Employee_fk_areaId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Employee_fk_areaId" ON public."tbl_Employee" USING btree ("areaId");


--
-- TOC entry 5210 (class 1259 OID 26277)
-- Name: fki_Employee_fk_designationId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Employee_fk_designationId" ON public."tbl_Employee" USING btree ("designationId");


--
-- TOC entry 5211 (class 1259 OID 26309)
-- Name: fki_Employee_fk_marketId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Employee_fk_marketId" ON public."tbl_Employee" USING btree ("marketId");


--
-- TOC entry 5212 (class 1259 OID 26315)
-- Name: fki_Employee_fk_suffixPrefixId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Employee_fk_suffixPrefixId" ON public."tbl_Employee" USING btree ("suffixPrefixId");


--
-- TOC entry 5213 (class 1259 OID 26290)
-- Name: fki_Employee_fk_workLocationId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Employee_fk_workLocationId" ON public."tbl_Employee" USING btree ("workLocationId");


--
-- TOC entry 5219 (class 1259 OID 26424)
-- Name: fki_LedgerPosting_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_LedgerPosting_fk_costCentreId" ON public."tbl_LedgerPosting" USING btree ("costCentreId");


--
-- TOC entry 5220 (class 1259 OID 26399)
-- Name: fki_LedgerPosting_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_LedgerPosting_fk_ledgerId" ON public."tbl_LedgerPosting" USING btree ("ledgerId");


--
-- TOC entry 5221 (class 1259 OID 26418)
-- Name: fki_LedgerPosting_fk_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_LedgerPosting_fk_voucherNo" ON public."tbl_LedgerPosting" USING btree ("voucherNo");


--
-- TOC entry 5236 (class 1259 OID 26547)
-- Name: fki_Partybalance_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Partybalance_fk_costCentreId" ON public."tbl_Partybalance" USING btree ("costCentreId");


--
-- TOC entry 5237 (class 1259 OID 26541)
-- Name: fki_Partybalance_fk_currecyConversionId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Partybalance_fk_currecyConversionId" ON public."tbl_Partybalance" USING btree ("currencyConversionId");


--
-- TOC entry 5238 (class 1259 OID 26529)
-- Name: fki_Partybalance_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Partybalance_fk_ledgerId" ON public."tbl_Partybalance" USING btree ("ledgerId");


--
-- TOC entry 5239 (class 1259 OID 26535)
-- Name: fki_Partybalance_fk_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Partybalance_fk_voucherNo" ON public."tbl_Partybalance" USING btree ("voucherNo");


--
-- TOC entry 5299 (class 1259 OID 34552)
-- Name: fki_QuotationDetails_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationDetails_fk_productCode" ON public."tbl_QuotationDetails" USING btree ("productCode");


--
-- TOC entry 5300 (class 1259 OID 34546)
-- Name: fki_QuotationDetails_fk_quotationMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationDetails_fk_quotationMasterId" ON public."tbl_QuotationDetails" USING btree ("quotationMasterId");


--
-- TOC entry 5301 (class 1259 OID 34564)
-- Name: fki_QuotationDetails_fk_taxId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationDetails_fk_taxId" ON public."tbl_QuotationDetails" USING btree ("taxId");


--
-- TOC entry 5302 (class 1259 OID 34558)
-- Name: fki_QuotationDetails_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationDetails_fk_unitId" ON public."tbl_QuotationDetails" USING btree ("unitId");


--
-- TOC entry 5289 (class 1259 OID 34483)
-- Name: fki_QuotationMaster_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationMaster_fk_costCentreId" ON public."tbl_QuotationMaster" USING btree ("costCentreId");


--
-- TOC entry 5290 (class 1259 OID 34471)
-- Name: fki_QuotationMaster_fk_currencyConversionId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationMaster_fk_currencyConversionId" ON public."tbl_QuotationMaster" USING btree ("currencyConversionId");


--
-- TOC entry 5291 (class 1259 OID 34459)
-- Name: fki_QuotationMaster_fk_employeeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationMaster_fk_employeeId" ON public."tbl_QuotationMaster" USING btree ("employeeId");


--
-- TOC entry 5292 (class 1259 OID 34477)
-- Name: fki_QuotationMaster_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationMaster_fk_ledgerId" ON public."tbl_QuotationMaster" USING btree ("ledgerId");


--
-- TOC entry 5293 (class 1259 OID 34465)
-- Name: fki_QuotationMaster_fk_pricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_QuotationMaster_fk_pricingLevelId" ON public."tbl_QuotationMaster" USING btree ("pricingLevelId");


--
-- TOC entry 5264 (class 1259 OID 26718)
-- Name: fki_ReceiptMaster_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_ReceiptMaster_fk_costCentreId" ON public."tbl_ReceiptMaster" USING btree ("costCentreId");


--
-- TOC entry 5265 (class 1259 OID 26712)
-- Name: fki_ReceiptMaster_fk_employeeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_ReceiptMaster_fk_employeeId" ON public."tbl_ReceiptMaster" USING btree ("employeeId");


--
-- TOC entry 5266 (class 1259 OID 26706)
-- Name: fki_ReceiptMaster_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_ReceiptMaster_fk_ledgerId" ON public."tbl_ReceiptMaster" USING btree ("ledgerId");


--
-- TOC entry 5271 (class 1259 OID 26747)
-- Name: fki_Receiptdetails_fk_currecyConversionId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Receiptdetails_fk_currecyConversionId" ON public."tbl_Receiptdetails" USING btree ("currencyConversionId");


--
-- TOC entry 5272 (class 1259 OID 26741)
-- Name: fki_Receiptdetails_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Receiptdetails_fk_ledgerId" ON public."tbl_Receiptdetails" USING btree ("ledgerId");


--
-- TOC entry 5273 (class 1259 OID 26735)
-- Name: fki_Receiptdetails_fk_receiptMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_Receiptdetails_fk_receiptMasterId" ON public."tbl_Receiptdetails" USING btree ("receiptMasterId");


--
-- TOC entry 5255 (class 1259 OID 26660)
-- Name: fki_SalesReturnDetails1_fk_GodownId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnDetails1_fk_GodownId" ON public."tbl_SalesReturnDetails1" USING btree ("GodownId");


--
-- TOC entry 5256 (class 1259 OID 26630)
-- Name: fki_SalesReturnDetails1_fk_ReturnMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnDetails1_fk_ReturnMasterId" ON public."tbl_SalesReturnDetails1" USING btree ("ReturnMasterId");


--
-- TOC entry 5257 (class 1259 OID 26642)
-- Name: fki_SalesReturnDetails1_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnDetails1_fk_productCode" ON public."tbl_SalesReturnDetails1" USING btree ("productCode");


--
-- TOC entry 5258 (class 1259 OID 26636)
-- Name: fki_SalesReturnDetails1_fk_salesDetails1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnDetails1_fk_salesDetails1Id" ON public."tbl_SalesReturnDetails1" USING btree ("salesDetails1Id");


--
-- TOC entry 5259 (class 1259 OID 26654)
-- Name: fki_SalesReturnDetails1_fk_taxId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnDetails1_fk_taxId" ON public."tbl_SalesReturnDetails1" USING btree ("taxId");


--
-- TOC entry 5260 (class 1259 OID 26648)
-- Name: fki_SalesReturnDetails1_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnDetails1_fk_unitId" ON public."tbl_SalesReturnDetails1" USING btree ("unitId");


--
-- TOC entry 5244 (class 1259 OID 45638)
-- Name: fki_SalesReturnMaster_fk_GodownId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnMaster_fk_GodownId" ON public."tbl_SalesReturnMaster" USING btree ("GodownId");


--
-- TOC entry 5245 (class 1259 OID 26615)
-- Name: fki_SalesReturnMaster_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnMaster_fk_costCentreId" ON public."tbl_SalesReturnMaster" USING btree ("costCentreId");


--
-- TOC entry 5246 (class 1259 OID 26609)
-- Name: fki_SalesReturnMaster_fk_employeeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnMaster_fk_employeeId" ON public."tbl_SalesReturnMaster" USING btree ("employeeId");


--
-- TOC entry 5247 (class 1259 OID 26597)
-- Name: fki_SalesReturnMaster_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnMaster_fk_ledgerId" ON public."tbl_SalesReturnMaster" USING btree ("ledgerId");


--
-- TOC entry 5248 (class 1259 OID 26603)
-- Name: fki_SalesReturnMaster_fk_pricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnMaster_fk_pricingLevelId" ON public."tbl_SalesReturnMaster" USING btree ("pricingLevelId");


--
-- TOC entry 5249 (class 1259 OID 26591)
-- Name: fki_SalesReturnMaster_fk_salesMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnMaster_fk_salesMasterId" ON public."tbl_SalesReturnMaster" USING btree ("salesMasterId");


--
-- TOC entry 5250 (class 1259 OID 26585)
-- Name: fki_SalesReturnMaster_fk_suffixPrefixId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SalesReturnMaster_fk_suffixPrefixId" ON public."tbl_SalesReturnMaster" USING btree ("suffixPrefixId");


--
-- TOC entry 5227 (class 1259 OID 26505)
-- Name: fki_StockPosting_fk_VoucherUnitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_StockPosting_fk_VoucherUnitId" ON public."tbl_StockPosting" USING btree ("VoucherUnitId");


--
-- TOC entry 5228 (class 1259 OID 26499)
-- Name: fki_StockPosting_fk_godownId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_StockPosting_fk_godownId" ON public."tbl_StockPosting" USING btree ("godownId");


--
-- TOC entry 5229 (class 1259 OID 26493)
-- Name: fki_StockPosting_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_StockPosting_fk_productCode" ON public."tbl_StockPosting" USING btree ("productCode");


--
-- TOC entry 5230 (class 1259 OID 26511)
-- Name: fki_StockPosting_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_StockPosting_fk_unitId" ON public."tbl_StockPosting" USING btree ("unitId");


--
-- TOC entry 5231 (class 1259 OID 26555)
-- Name: fki_StockPosting_fk_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_StockPosting_fk_voucherNo" ON public."tbl_StockPosting" USING btree ("voucherNo");


--
-- TOC entry 5189 (class 1259 OID 18314)
-- Name: fki_SuffixPrefix_fk_yearId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_SuffixPrefix_fk_yearId" ON public."tbl_SuffixPrefix" USING btree ("yearId");


--
-- TOC entry 5316 (class 1259 OID 37418)
-- Name: fki_proformaInvoiceDetails1_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceDetails1_fk_productCode" ON public."tbl_proformaInvoiceDetails1" USING btree ("productCode");


--
-- TOC entry 5317 (class 1259 OID 37412)
-- Name: fki_proformaInvoiceDetails1_fk_proformaMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceDetails1_fk_proformaMasterId" ON public."tbl_proformaInvoiceDetails1" USING btree ("proformaMasterId");


--
-- TOC entry 5318 (class 1259 OID 37430)
-- Name: fki_proformaInvoiceDetails1_fk_quotationDetailsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceDetails1_fk_quotationDetailsId" ON public."tbl_proformaInvoiceDetails1" USING btree ("quotationDetailsId");


--
-- TOC entry 5319 (class 1259 OID 37436)
-- Name: fki_proformaInvoiceDetails1_fk_taxId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceDetails1_fk_taxId" ON public."tbl_proformaInvoiceDetails1" USING btree ("taxId");


--
-- TOC entry 5320 (class 1259 OID 37424)
-- Name: fki_proformaInvoiceDetails1_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceDetails1_fk_unitId" ON public."tbl_proformaInvoiceDetails1" USING btree ("unitId");


--
-- TOC entry 5306 (class 1259 OID 37397)
-- Name: fki_proformaInvoiceMaster_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceMaster_fk_costCentreId" ON public."tbl_proformaInvoiceMaster" USING btree ("costCentreId");


--
-- TOC entry 5307 (class 1259 OID 37391)
-- Name: fki_proformaInvoiceMaster_fk_currencyConversionId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceMaster_fk_currencyConversionId" ON public."tbl_proformaInvoiceMaster" USING btree ("currencyConversionId");


--
-- TOC entry 5308 (class 1259 OID 37385)
-- Name: fki_proformaInvoiceMaster_fk_employeeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceMaster_fk_employeeId" ON public."tbl_proformaInvoiceMaster" USING btree ("employeeId");


--
-- TOC entry 5309 (class 1259 OID 37373)
-- Name: fki_proformaInvoiceMaster_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceMaster_fk_ledgerId" ON public."tbl_proformaInvoiceMaster" USING btree ("ledgerId");


--
-- TOC entry 5310 (class 1259 OID 37379)
-- Name: fki_proformaInvoiceMaster_fk_pricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceMaster_fk_pricingLevelId" ON public."tbl_proformaInvoiceMaster" USING btree ("pricingLevelId");


--
-- TOC entry 5311 (class 1259 OID 37534)
-- Name: fki_proformaInvoiceMaster_fk_quotationMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_proformaInvoiceMaster_fk_quotationMasterId" ON public."tbl_proformaInvoiceMaster" USING btree ("quotationMasterId");


--
-- TOC entry 5114 (class 1259 OID 17897)
-- Name: fki_tbl_AccountLedger_fk_areaId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_AccountLedger_fk_areaId" ON public."tbl_AccountLedger" USING btree ("areaId");


--
-- TOC entry 5115 (class 1259 OID 17853)
-- Name: fki_tbl_AccountLedger_fk_currencyId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_AccountLedger_fk_currencyId" ON public."tbl_AccountLedger" USING btree ("currencyId");


--
-- TOC entry 5116 (class 1259 OID 17784)
-- Name: fki_tbl_AccountLedger_fk_groupId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_AccountLedger_fk_groupId" ON public."tbl_AccountLedger" USING btree ("groupId");


--
-- TOC entry 5117 (class 1259 OID 17801)
-- Name: fki_tbl_AccountLedger_fk_marketId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_AccountLedger_fk_marketId" ON public."tbl_AccountLedger" USING btree ("marketId");


--
-- TOC entry 5118 (class 1259 OID 17846)
-- Name: fki_tbl_AccountLedger_fk_pricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_AccountLedger_fk_pricingLevelId" ON public."tbl_AccountLedger" USING btree ("pricingLevelId");


--
-- TOC entry 5119 (class 1259 OID 17886)
-- Name: fki_tbl_AccountLedger_fk_routeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_AccountLedger_fk_routeId" ON public."tbl_AccountLedger" USING btree ("areaId");


--
-- TOC entry 5152 (class 1259 OID 18099)
-- Name: fki_tbl_BOM_fk_UnitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_BOM_fk_UnitId" ON public."tbl_BOM" USING btree ("UnitId");


--
-- TOC entry 5153 (class 1259 OID 18087)
-- Name: fki_tbl_BOM_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_BOM_fk_productCode" ON public."tbl_BOM" USING btree ("productCode");


--
-- TOC entry 5154 (class 1259 OID 18093)
-- Name: fki_tbl_BOM_fk_rowMaterialId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_BOM_fk_rowMaterialId" ON public."tbl_BOM" USING btree ("rowMaterialId");


--
-- TOC entry 5159 (class 1259 OID 18120)
-- Name: fki_tbl_CurrencyConversion_fk_currencyId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_CurrencyConversion_fk_currencyId" ON public."tbl_CurrencyConversion" USING btree ("currencyId");


--
-- TOC entry 5357 (class 1259 OID 62049)
-- Name: fki_tbl_DeliveryNoteDetails1_fk_GodownId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteDetails1_fk_GodownId" ON public."tbl_DeliveryNoteDetails1" USING btree ("GodownId");


--
-- TOC entry 5358 (class 1259 OID 62024)
-- Name: fki_tbl_DeliveryNoteDetails1_fk_deliveryNoteMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteDetails1_fk_deliveryNoteMasterId" ON public."tbl_DeliveryNoteDetails1" USING btree ("deliveryNoteMasterId");


--
-- TOC entry 5359 (class 1259 OID 62055)
-- Name: fki_tbl_DeliveryNoteDetails1_fk_orderDetails1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteDetails1_fk_orderDetails1Id" ON public."tbl_DeliveryNoteDetails1" USING btree ("orderDetails1Id");


--
-- TOC entry 5360 (class 1259 OID 62030)
-- Name: fki_tbl_DeliveryNoteDetails1_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteDetails1_fk_productCode" ON public."tbl_DeliveryNoteDetails1" USING btree ("productCode");


--
-- TOC entry 5361 (class 1259 OID 62067)
-- Name: fki_tbl_DeliveryNoteDetails1_fk_proformaDetails1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteDetails1_fk_proformaDetails1Id" ON public."tbl_DeliveryNoteDetails1" USING btree ("proformaDetails1Id");


--
-- TOC entry 5362 (class 1259 OID 62061)
-- Name: fki_tbl_DeliveryNoteDetails1_fk_quotationDetailsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteDetails1_fk_quotationDetailsId" ON public."tbl_DeliveryNoteDetails1" USING btree ("quotationDetailsId");


--
-- TOC entry 5363 (class 1259 OID 62043)
-- Name: fki_tbl_DeliveryNoteDetails1_fk_taxId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteDetails1_fk_taxId" ON public."tbl_DeliveryNoteDetails1" USING btree ("taxId");


--
-- TOC entry 5364 (class 1259 OID 62036)
-- Name: fki_tbl_DeliveryNoteDetails1_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteDetails1_fk_unitId" ON public."tbl_DeliveryNoteDetails1" USING btree ("unitId");


--
-- TOC entry 5345 (class 1259 OID 54375)
-- Name: fki_tbl_DeliveryNoteMaster_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteMaster_fk_costCentreId" ON public."tbl_DeliveryNoteMaster" USING btree ("costCentreId");


--
-- TOC entry 5346 (class 1259 OID 54393)
-- Name: fki_tbl_DeliveryNoteMaster_fk_currencyConversionId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteMaster_fk_currencyConversionId" ON public."tbl_DeliveryNoteMaster" USING btree ("currencyConversionId");


--
-- TOC entry 5347 (class 1259 OID 54387)
-- Name: fki_tbl_DeliveryNoteMaster_fk_employeeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteMaster_fk_employeeId" ON public."tbl_DeliveryNoteMaster" USING btree ("employeeId");


--
-- TOC entry 5348 (class 1259 OID 54369)
-- Name: fki_tbl_DeliveryNoteMaster_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteMaster_fk_ledgerId" ON public."tbl_DeliveryNoteMaster" USING btree ("ledgerId");


--
-- TOC entry 5349 (class 1259 OID 54399)
-- Name: fki_tbl_DeliveryNoteMaster_fk_orderMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteMaster_fk_orderMasterId" ON public."tbl_DeliveryNoteMaster" USING btree ("orderMasterId");


--
-- TOC entry 5350 (class 1259 OID 54381)
-- Name: fki_tbl_DeliveryNoteMaster_fk_pricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteMaster_fk_pricingLevelId" ON public."tbl_DeliveryNoteMaster" USING btree ("pricingLevelId");


--
-- TOC entry 5351 (class 1259 OID 54416)
-- Name: fki_tbl_DeliveryNoteMaster_fk_proformaMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteMaster_fk_proformaMasterId" ON public."tbl_DeliveryNoteMaster" USING btree ("proformaMasterId");


--
-- TOC entry 5352 (class 1259 OID 54405)
-- Name: fki_tbl_DeliveryNoteMaster_fk_quotationMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_DeliveryNoteMaster_fk_quotationMasterId" ON public."tbl_DeliveryNoteMaster" USING btree ("proformaMasterId");


--
-- TOC entry 5145 (class 1259 OID 17995)
-- Name: fki_tbl_FinancialYearMonth_fk_yearId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_FinancialYearMonth_fk_yearId" ON public."tbl_FinancialYearMonth" USING btree ("yearId");


--
-- TOC entry 5128 (class 1259 OID 17557)
-- Name: fki_tbl_ProductSalesPrice_fk_pricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_ProductSalesPrice_fk_pricingLevelId" ON public."tbl_ProductSalesPrice" USING btree ("PricingLevelId");


--
-- TOC entry 5129 (class 1259 OID 17642)
-- Name: fki_tbl_ProductSalesPrice_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_ProductSalesPrice_fk_productCode" ON public."tbl_ProductSalesPrice" USING btree ("productCode");


--
-- TOC entry 5130 (class 1259 OID 17551)
-- Name: fki_tbl_ProductSalesPrice_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_ProductSalesPrice_fk_unitId" ON public."tbl_ProductSalesPrice" USING btree ("unitId");


--
-- TOC entry 5278 (class 1259 OID 27307)
-- Name: fki_tbl_Product_fk_brandId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_Product_fk_brandId" ON public."tbl_Product" USING btree ("brandId");


--
-- TOC entry 5279 (class 1259 OID 27308)
-- Name: fki_tbl_Product_fk_group1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_Product_fk_group1Id" ON public."tbl_Product" USING btree ("group1Id");


--
-- TOC entry 5280 (class 1259 OID 27309)
-- Name: fki_tbl_Product_fk_group2Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_Product_fk_group2Id" ON public."tbl_Product" USING btree ("group2Id");


--
-- TOC entry 5281 (class 1259 OID 27310)
-- Name: fki_tbl_Product_fk_group3Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_Product_fk_group3Id" ON public."tbl_Product" USING btree ("group3Id");


--
-- TOC entry 5282 (class 1259 OID 27311)
-- Name: fki_tbl_Product_fk_group4Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_Product_fk_group4Id" ON public."tbl_Product" USING btree ("group4Id");


--
-- TOC entry 5283 (class 1259 OID 27312)
-- Name: fki_tbl_Product_fk_groupCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_Product_fk_groupCode" ON public."tbl_Product" USING btree ("groupCode");


--
-- TOC entry 5284 (class 1259 OID 27313)
-- Name: fki_tbl_Product_fk_groupId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_Product_fk_groupId" ON public."tbl_Product" USING btree ("group1Id");


--
-- TOC entry 5285 (class 1259 OID 27314)
-- Name: fki_tbl_Product_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_Product_fk_unitId" ON public."tbl_Product" USING btree ("unitId");


--
-- TOC entry 5179 (class 1259 OID 18226)
-- Name: fki_tbl_SalesDetails1_fk_GodownId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesDetails1_fk_GodownId" ON public."tbl_SalesDetails1" USING btree ("GodownId");


--
-- TOC entry 5180 (class 1259 OID 27433)
-- Name: fki_tbl_SalesDetails1_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesDetails1_fk_productCode" ON public."tbl_SalesDetails1" USING btree ("productCode");


--
-- TOC entry 5181 (class 1259 OID 18208)
-- Name: fki_tbl_SalesDetails1_fk_salesMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesDetails1_fk_salesMasterId" ON public."tbl_SalesDetails1" USING btree ("salesMasterId");


--
-- TOC entry 5182 (class 1259 OID 18220)
-- Name: fki_tbl_SalesDetails1_fk_taxId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesDetails1_fk_taxId" ON public."tbl_SalesDetails1" USING btree ("taxId");


--
-- TOC entry 5183 (class 1259 OID 18214)
-- Name: fki_tbl_SalesDetails1_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesDetails1_fk_unitId" ON public."tbl_SalesDetails1" USING btree ("unitId");


--
-- TOC entry 5167 (class 1259 OID 18182)
-- Name: fki_tbl_SalesMaster_fk_GodownId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesMaster_fk_GodownId" ON public."tbl_SalesMaster" USING btree ("GodownId");


--
-- TOC entry 5168 (class 1259 OID 18188)
-- Name: fki_tbl_SalesMaster_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesMaster_fk_costCentreId" ON public."tbl_SalesMaster" USING btree ("costCentreId");


--
-- TOC entry 5169 (class 1259 OID 18176)
-- Name: fki_tbl_SalesMaster_fk_currecyConversionId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesMaster_fk_currecyConversionId" ON public."tbl_SalesMaster" USING btree ("currencyConversionId");


--
-- TOC entry 5170 (class 1259 OID 26381)
-- Name: fki_tbl_SalesMaster_fk_employeeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesMaster_fk_employeeId" ON public."tbl_SalesMaster" USING btree ("employeeId");


--
-- TOC entry 5171 (class 1259 OID 18164)
-- Name: fki_tbl_SalesMaster_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesMaster_fk_ledgerId" ON public."tbl_SalesMaster" USING btree ("ledgerId");


--
-- TOC entry 5172 (class 1259 OID 18170)
-- Name: fki_tbl_SalesMaster_fk_pricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesMaster_fk_pricingLevelId" ON public."tbl_SalesMaster" USING btree ("pricingLevelId");


--
-- TOC entry 5173 (class 1259 OID 26369)
-- Name: fki_tbl_SalesMaster_fk_suffixPrefixId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesMaster_fk_suffixPrefixId" ON public."tbl_SalesMaster" USING btree ("suffixPrefixId");


--
-- TOC entry 5324 (class 1259 OID 53977)
-- Name: fki_tbl_SalesOrderMaster_fk_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderMaster_fk_costCentreId" ON public."tbl_SalesOrderMaster" USING btree ("costCentreId");


--
-- TOC entry 5325 (class 1259 OID 53983)
-- Name: fki_tbl_SalesOrderMaster_fk_currencyConversionId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderMaster_fk_currencyConversionId" ON public."tbl_SalesOrderMaster" USING btree ("currencyConversionId");


--
-- TOC entry 5326 (class 1259 OID 53971)
-- Name: fki_tbl_SalesOrderMaster_fk_employeeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderMaster_fk_employeeId" ON public."tbl_SalesOrderMaster" USING btree ("employeeId");


--
-- TOC entry 5327 (class 1259 OID 53959)
-- Name: fki_tbl_SalesOrderMaster_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderMaster_fk_ledgerId" ON public."tbl_SalesOrderMaster" USING btree ("ledgerId");


--
-- TOC entry 5328 (class 1259 OID 53965)
-- Name: fki_tbl_SalesOrderMaster_fk_pricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderMaster_fk_pricingLevelId" ON public."tbl_SalesOrderMaster" USING btree ("pricingLevelId");


--
-- TOC entry 5329 (class 1259 OID 53996)
-- Name: fki_tbl_SalesOrderMaster_fk_proformaMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderMaster_fk_proformaMasterId" ON public."tbl_SalesOrderMaster" USING btree ("proformaMasterId");


--
-- TOC entry 5330 (class 1259 OID 53989)
-- Name: fki_tbl_SalesOrderMaster_fk_quotationMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderMaster_fk_quotationMasterId" ON public."tbl_SalesOrderMaster" USING btree ("quotationMasterId");


--
-- TOC entry 5336 (class 1259 OID 54010)
-- Name: fki_tbl_SalesOrderdetails1_fk_orderMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderdetails1_fk_orderMasterId" ON public."tbl_SalesOrderdetails1" USING btree ("orderMasterId");


--
-- TOC entry 5337 (class 1259 OID 54029)
-- Name: fki_tbl_SalesOrderdetails1_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderdetails1_fk_productCode" ON public."tbl_SalesOrderdetails1" USING btree ("productCode");


--
-- TOC entry 5338 (class 1259 OID 54023)
-- Name: fki_tbl_SalesOrderdetails1_fk_proformaDetails1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderdetails1_fk_proformaDetails1Id" ON public."tbl_SalesOrderdetails1" USING btree ("proformaDetails1Id");


--
-- TOC entry 5339 (class 1259 OID 54017)
-- Name: fki_tbl_SalesOrderdetails1_fk_quotationDetailsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderdetails1_fk_quotationDetailsId" ON public."tbl_SalesOrderdetails1" USING btree ("quotationDetailsId");


--
-- TOC entry 5340 (class 1259 OID 54041)
-- Name: fki_tbl_SalesOrderdetails1_fk_taxId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderdetails1_fk_taxId" ON public."tbl_SalesOrderdetails1" USING btree ("taxId");


--
-- TOC entry 5341 (class 1259 OID 54035)
-- Name: fki_tbl_SalesOrderdetails1_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_SalesOrderdetails1_fk_unitId" ON public."tbl_SalesOrderdetails1" USING btree ("unitId");


--
-- TOC entry 5163 (class 1259 OID 18138)
-- Name: fki_tbl_ShippingAddress_fk_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_ShippingAddress_fk_ledgerId" ON public."tbl_ShippingAddress" USING btree ("ledgerId");


--
-- TOC entry 5135 (class 1259 OID 17589)
-- Name: fki_tbl_UnitConversion_fk_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_UnitConversion_fk_productCode" ON public."tbl_UnitConversion" USING btree ("productCode");


--
-- TOC entry 5136 (class 1259 OID 17576)
-- Name: fki_tbl_UnitConversion_fk_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "fki_tbl_UnitConversion_fk_unitId" ON public."tbl_UnitConversion" USING btree ("unitId");


--
-- TOC entry 5084 (class 1259 OID 16980)
-- Name: idex_market_MarketId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idex_market_MarketId" ON public."tbl_Market" USING btree ("MarketId") WITH (deduplicate_items='true');


--
-- TOC entry 5056 (class 1259 OID 16981)
-- Name: idx_AccountGroup_AccountGroupCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_AccountGroup_AccountGroupCode" ON public."tbl_AccountGroup" USING btree ("AccountGroupCode") WITH (deduplicate_items='true');


--
-- TOC entry 5057 (class 1259 OID 16982)
-- Name: idx_AccountGroup_accountGroupName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_AccountGroup_accountGroupName" ON public."tbl_AccountGroup" USING btree ("accountGroupName") WITH (deduplicate_items='true');


--
-- TOC entry 5058 (class 1259 OID 16983)
-- Name: idx_AccountGroup_groupId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_AccountGroup_groupId" ON public."tbl_AccountGroup" USING btree ("groupId") WITH (deduplicate_items='true');


--
-- TOC entry 5059 (class 1259 OID 17953)
-- Name: idx_AccountGroup_groupUnder; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_AccountGroup_groupUnder" ON public."tbl_AccountGroup" USING btree ("groupUnder") WITH (deduplicate_items='true');


--
-- TOC entry 5120 (class 1259 OID 17409)
-- Name: idx_AccountLedger_ledgerId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_AccountLedger_ledgerId" ON public."tbl_AccountLedger" USING btree ("ledgerId") WITH (deduplicate_items='true');


--
-- TOC entry 5121 (class 1259 OID 17410)
-- Name: idx_AccountLedger_ledgerName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_AccountLedger_ledgerName" ON public."tbl_AccountLedger" USING btree ("ledgerName") WITH (deduplicate_items='true');


--
-- TOC entry 5066 (class 1259 OID 16984)
-- Name: idx_CostCentre; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_CostCentre" ON public."tbl_CostCentre" USING btree ("CostCentre") WITH (deduplicate_items='true');


--
-- TOC entry 5160 (class 1259 OID 18114)
-- Name: idx_CurrencyConversion_currecyConversionId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_CurrencyConversion_currecyConversionId" ON public."tbl_CurrencyConversion" USING btree ("currencyConversionId") WITH (deduplicate_items='true');


--
-- TOC entry 5070 (class 1259 OID 16985)
-- Name: idx_Currency_currencyId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Currency_currencyId" ON public."tbl_Currency" USING btree ("currencyId") WITH (deduplicate_items='true');


--
-- TOC entry 5071 (class 1259 OID 17752)
-- Name: idx_Currency_currencyName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Currency_currencyName" ON public."tbl_Currency" USING btree ("currencyName") WITH (deduplicate_items='true');


--
-- TOC entry 5199 (class 1259 OID 26215)
-- Name: idx_Department_departmentId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Department_departmentId" ON public."tbl_Department" USING btree ("departmentId") WITH (deduplicate_items='true');


--
-- TOC entry 5200 (class 1259 OID 26216)
-- Name: idx_Department_departmentName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Department_departmentName" ON public."tbl_Department" USING btree ("departmentName") WITH (deduplicate_items='true');


--
-- TOC entry 5195 (class 1259 OID 18369)
-- Name: idx_Designation_designationId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Designation_designationId" ON public."tbl_Designation" USING btree ("designationId") WITH (deduplicate_items='true');


--
-- TOC entry 5196 (class 1259 OID 18370)
-- Name: idx_Designation_designationName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Designation_designationName" ON public."tbl_Designation" USING btree ("designationName") WITH (deduplicate_items='true');


--
-- TOC entry 5214 (class 1259 OID 26270)
-- Name: idx_Employee_employeeCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Employee_employeeCode" ON public."tbl_Employee" USING btree ("employeeCode") WITH (deduplicate_items='true');


--
-- TOC entry 5215 (class 1259 OID 26269)
-- Name: idx_Employee_employeeId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Employee_employeeId" ON public."tbl_Employee" USING btree ("employeeId") WITH (deduplicate_items='true');


--
-- TOC entry 5216 (class 1259 OID 26271)
-- Name: idx_Employee_employeeName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Employee_employeeName" ON public."tbl_Employee" USING btree ("employeeName") WITH (deduplicate_items='true');


--
-- TOC entry 5074 (class 1259 OID 16986)
-- Name: idx_FinanceSettings_FinanceSettingsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinanceSettings_FinanceSettingsId" ON public."tbl_FinanceSettings" USING btree ("FinanceSettingsId") WITH (deduplicate_items='true');


--
-- TOC entry 5146 (class 1259 OID 17988)
-- Name: idx_FinancialYearMonth_MonthName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinancialYearMonth_MonthName" ON public."tbl_FinancialYearMonth" USING btree ("MonthName") WITH (deduplicate_items='true');


--
-- TOC entry 5147 (class 1259 OID 17989)
-- Name: idx_FinancialYearMonth_closed; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinancialYearMonth_closed" ON public."tbl_FinancialYearMonth" USING btree (closed) WITH (deduplicate_items='true');


--
-- TOC entry 5148 (class 1259 OID 17986)
-- Name: idx_FinancialYearMonth_monthId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinancialYearMonth_monthId" ON public."tbl_FinancialYearMonth" USING btree ("monthId") WITH (deduplicate_items='true');


--
-- TOC entry 5149 (class 1259 OID 17987)
-- Name: idx_FinancialYearMonth_yearId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinancialYearMonth_yearId" ON public."tbl_FinancialYearMonth" USING btree ("yearId") WITH (deduplicate_items='true');


--
-- TOC entry 5139 (class 1259 OID 17979)
-- Name: idx_FinancialYear_closed; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinancialYear_closed" ON public."tbl_FinancialYear" USING btree (closed) WITH (deduplicate_items='true');


--
-- TOC entry 5140 (class 1259 OID 17977)
-- Name: idx_FinancialYear_fromDate; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinancialYear_fromDate" ON public."tbl_FinancialYear" USING btree ("fromDate") WITH (deduplicate_items='true');


--
-- TOC entry 5141 (class 1259 OID 17978)
-- Name: idx_FinancialYear_toDate; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinancialYear_toDate" ON public."tbl_FinancialYear" USING btree ("toDate") WITH (deduplicate_items='true');


--
-- TOC entry 5142 (class 1259 OID 17976)
-- Name: idx_FinancialYear_yearId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_FinancialYear_yearId" ON public."tbl_FinancialYear" USING btree ("yearId") WITH (deduplicate_items='true');


--
-- TOC entry 5044 (class 1259 OID 16987)
-- Name: idx_Godown_GodownId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Godown_GodownId" ON public."tbl_Godown" USING btree ("GodownId") WITH (deduplicate_items='true');


--
-- TOC entry 5045 (class 1259 OID 16988)
-- Name: idx_Godown_GodownName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Godown_GodownName" ON public."tbl_Godown" USING btree ("GodownName") WITH (deduplicate_items='true');


--
-- TOC entry 5081 (class 1259 OID 16989)
-- Name: idx_InventorySettings_InventorySettingsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_InventorySettings_InventorySettingsId" ON public."tbl_InventorySettings" USING btree ("InventorySettingsId") WITH (deduplicate_items='true');


--
-- TOC entry 5222 (class 1259 OID 26391)
-- Name: idx_LedgerPosting_ledgerPostingId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_LedgerPosting_ledgerPostingId" ON public."tbl_LedgerPosting" USING btree ("ledgerPostingId") WITH (deduplicate_items='true');


--
-- TOC entry 5223 (class 1259 OID 26412)
-- Name: idx_LedgerPosting_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_LedgerPosting_voucherNo" ON public."tbl_LedgerPosting" USING btree ("voucherNo") WITH (deduplicate_items='true');


--
-- TOC entry 5224 (class 1259 OID 26393)
-- Name: idx_LedgerPosting_voucherType; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_LedgerPosting_voucherType" ON public."tbl_LedgerPosting" USING btree ("voucherType") WITH (deduplicate_items='true');


--
-- TOC entry 5240 (class 1259 OID 26522)
-- Name: idx_Partybalance_balanceId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Partybalance_balanceId" ON public."tbl_Partybalance" USING btree ("balanceId") WITH (deduplicate_items='true');


--
-- TOC entry 5241 (class 1259 OID 26523)
-- Name: idx_Partybalance_voucherType; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Partybalance_voucherType" ON public."tbl_Partybalance" USING btree ("voucherType") WITH (deduplicate_items='true');


--
-- TOC entry 5048 (class 1259 OID 16990)
-- Name: idx_PricingLevel_PricingLevelId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_PricingLevel_PricingLevelId" ON public."tbl_PricingLevel" USING btree ("PricingLevelId") WITH (deduplicate_items='true');


--
-- TOC entry 5049 (class 1259 OID 16991)
-- Name: idx_PricingLevel_PricingLevelName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_PricingLevel_PricingLevelName" ON public."tbl_PricingLevel" USING btree ("PricingLevelName") WITH (deduplicate_items='true');


--
-- TOC entry 5106 (class 1259 OID 17384)
-- Name: idx_ProductGroup_groupId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ProductGroup_groupId" ON public."tbl_ProductGroup" USING btree ("groupId") WITH (deduplicate_items='true');


--
-- TOC entry 5107 (class 1259 OID 17385)
-- Name: idx_ProductGroup_groupName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ProductGroup_groupName" ON public."tbl_ProductGroup" USING btree ("groupName") WITH (deduplicate_items='true');


--
-- TOC entry 5102 (class 1259 OID 17616)
-- Name: idx_ProductMainGroup_groupCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ProductMainGroup_groupCode" ON public."tbl_ProductMainGroup" USING btree ("groupCode") WITH (deduplicate_items='true');


--
-- TOC entry 5103 (class 1259 OID 17373)
-- Name: idx_ProductMainGroup_groupName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ProductMainGroup_groupName" ON public."tbl_ProductMainGroup" USING btree ("groupName") WITH (deduplicate_items='true');


--
-- TOC entry 5088 (class 1259 OID 16992)
-- Name: idx_PurchaseSettings_PurchaseSettingsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_PurchaseSettings_PurchaseSettingsId" ON public."tbl_PurchaseSettings" USING btree ("PurchaseSettingsId") WITH (deduplicate_items='true');


--
-- TOC entry 5267 (class 1259 OID 26692)
-- Name: idx_ReceiptMaster_receiptMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ReceiptMaster_receiptMasterId" ON public."tbl_ReceiptMaster" USING btree ("receiptMasterId") WITH (deduplicate_items='true');


--
-- TOC entry 5268 (class 1259 OID 26693)
-- Name: idx_ReceiptMaster_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ReceiptMaster_voucherNo" ON public."tbl_ReceiptMaster" USING btree ("voucherNo") WITH (deduplicate_items='true');


--
-- TOC entry 5274 (class 1259 OID 26729)
-- Name: idx_Receiptdetails_SlNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Receiptdetails_SlNo" ON public."tbl_Receiptdetails" USING btree ("SlNo") WITH (deduplicate_items='true');


--
-- TOC entry 5275 (class 1259 OID 26728)
-- Name: idx_Receiptdetails_receiptDetailsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Receiptdetails_receiptDetailsId" ON public."tbl_Receiptdetails" USING btree ("receiptDetailsId") WITH (deduplicate_items='true');


--
-- TOC entry 5251 (class 1259 OID 26578)
-- Name: idx_ReturnMaster_ReturnMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ReturnMaster_ReturnMasterId" ON public."tbl_SalesReturnMaster" USING btree ("ReturnMasterId") WITH (deduplicate_items='true');


--
-- TOC entry 5252 (class 1259 OID 26579)
-- Name: idx_ReturnMaster_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ReturnMaster_voucherNo" ON public."tbl_SalesReturnMaster" USING btree ("voucherNo") WITH (deduplicate_items='true');


--
-- TOC entry 5052 (class 1259 OID 16993)
-- Name: idx_Route_RouteId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Route_RouteId" ON public."tbl_Route" USING btree ("RouteId") WITH (deduplicate_items='true');


--
-- TOC entry 5053 (class 1259 OID 16994)
-- Name: idx_Route_RouteName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_Route_RouteName" ON public."tbl_Route" USING btree ("RouteName") WITH (deduplicate_items='true');


--
-- TOC entry 5184 (class 1259 OID 18202)
-- Name: idx_SalesDetails1_SlNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesDetails1_SlNo" ON public."tbl_SalesDetails1" USING btree ("SlNo") WITH (deduplicate_items='true');


--
-- TOC entry 5185 (class 1259 OID 18201)
-- Name: idx_SalesDetails1_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesDetails1_productCode" ON public."tbl_SalesDetails1" USING btree ("productCode") WITH (deduplicate_items='true');


--
-- TOC entry 5186 (class 1259 OID 18200)
-- Name: idx_SalesDetails1_salesDetails1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesDetails1_salesDetails1Id" ON public."tbl_SalesDetails1" USING btree ("salesDetails1Id") WITH (deduplicate_items='true');


--
-- TOC entry 5174 (class 1259 OID 18158)
-- Name: idx_SalesMaster_invoiceNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesMaster_invoiceNo" ON public."tbl_SalesMaster" USING btree ("invoiceNo") WITH (deduplicate_items='true');


--
-- TOC entry 5175 (class 1259 OID 18156)
-- Name: idx_SalesMaster_salesMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesMaster_salesMasterId" ON public."tbl_SalesMaster" USING btree ("salesMasterId") WITH (deduplicate_items='true');


--
-- TOC entry 5176 (class 1259 OID 18157)
-- Name: idx_SalesMaster_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesMaster_voucherNo" ON public."tbl_SalesMaster" USING btree ("voucherNo") WITH (deduplicate_items='true');


--
-- TOC entry 5342 (class 1259 OID 54004)
-- Name: idx_SalesOrderdetails1_orderDetails1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesOrderdetails1_orderDetails1Id" ON public."tbl_SalesOrderdetails1" USING btree ("orderDetails1Id") WITH (deduplicate_items='true');


--
-- TOC entry 5261 (class 1259 OID 26624)
-- Name: idx_SalesReturnDetails1_ReturnDetails1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesReturnDetails1_ReturnDetails1Id" ON public."tbl_SalesReturnDetails1" USING btree ("ReturnDetails1Id") WITH (deduplicate_items='true');


--
-- TOC entry 5091 (class 1259 OID 16995)
-- Name: idx_SalesSettings_SalesSettingsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesSettings_SalesSettingsId" ON public."tbl_SalesSettings" USING btree ("SalesSettingsId") WITH (deduplicate_items='true');


--
-- TOC entry 5331 (class 1259 OID 53951)
-- Name: idx_SalesorderMaster_orderMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesorderMaster_orderMasterId" ON public."tbl_SalesOrderMaster" USING btree ("orderMasterId") WITH (deduplicate_items='true');


--
-- TOC entry 5332 (class 1259 OID 53953)
-- Name: idx_SalesorderMaster_orderNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesorderMaster_orderNo" ON public."tbl_SalesOrderMaster" USING btree ("orderNo") WITH (deduplicate_items='true');


--
-- TOC entry 5333 (class 1259 OID 53952)
-- Name: idx_SalesorderMaster_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SalesorderMaster_voucherNo" ON public."tbl_SalesOrderMaster" USING btree ("voucherNo") WITH (deduplicate_items='true');


--
-- TOC entry 5164 (class 1259 OID 18132)
-- Name: idx_ShippingAddress_addressId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_ShippingAddress_addressId" ON public."tbl_ShippingAddress" USING btree ("addressId") WITH (deduplicate_items='true');


--
-- TOC entry 5232 (class 1259 OID 26480)
-- Name: idx_StockPosting_stockPostingId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_StockPosting_stockPostingId" ON public."tbl_StockPosting" USING btree ("stockPostingId") WITH (deduplicate_items='true');


--
-- TOC entry 5233 (class 1259 OID 26481)
-- Name: idx_StockPosting_voucherType; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_StockPosting_voucherType" ON public."tbl_StockPosting" USING btree ("voucherType") WITH (deduplicate_items='true');


--
-- TOC entry 5190 (class 1259 OID 18306)
-- Name: idx_SuffixPrefix_suffixPrefixId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SuffixPrefix_suffixPrefixId" ON public."tbl_SuffixPrefix" USING btree ("suffixPrefixId") WITH (deduplicate_items='true');


--
-- TOC entry 5191 (class 1259 OID 18307)
-- Name: idx_SuffixPrefix_voucherType; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SuffixPrefix_voucherType" ON public."tbl_SuffixPrefix" USING btree ("voucherType") WITH (deduplicate_items='true');


--
-- TOC entry 5192 (class 1259 OID 18308)
-- Name: idx_SuffixPrefix_yearId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_SuffixPrefix_yearId" ON public."tbl_SuffixPrefix" USING btree ("yearId") WITH (deduplicate_items='true');


--
-- TOC entry 5110 (class 1259 OID 17395)
-- Name: idx_TaxMaster_taxId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_TaxMaster_taxId" ON public."tbl_TaxMaster" USING btree ("taxId") WITH (deduplicate_items='true');


--
-- TOC entry 5111 (class 1259 OID 17396)
-- Name: idx_TaxMaster_taxName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_TaxMaster_taxName" ON public."tbl_TaxMaster" USING btree ("taxName") WITH (deduplicate_items='true');


--
-- TOC entry 5062 (class 1259 OID 16998)
-- Name: idx_area_AreaId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_area_AreaId" ON public."tbl_Area" USING btree ("AreaId") WITH (deduplicate_items='true');


--
-- TOC entry 5063 (class 1259 OID 16999)
-- Name: idx_area_AreaName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_area_AreaName" ON public."tbl_Area" USING btree ("AreaName") WITH (deduplicate_items='true');


--
-- TOC entry 5155 (class 1259 OID 18081)
-- Name: idx_bom_bomId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_bom_bomId" ON public."tbl_BOM" USING btree ("bomId") WITH (deduplicate_items='true');


--
-- TOC entry 5156 (class 1259 OID 18080)
-- Name: idx_bom_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_bom_productCode" ON public."tbl_BOM" USING btree ("bomId") WITH (deduplicate_items='true');


--
-- TOC entry 5040 (class 1259 OID 17003)
-- Name: idx_brand_brandId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_brand_brandId" ON public."tbl_Brand" USING btree ("brandId") WITH (deduplicate_items='true');


--
-- TOC entry 5041 (class 1259 OID 17004)
-- Name: idx_brand_brandName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_brand_brandName" ON public."tbl_Brand" USING btree ("brandName") WITH (deduplicate_items='true');


--
-- TOC entry 5067 (class 1259 OID 17007)
-- Name: idx_costCentreId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_costCentreId" ON public."tbl_CostCentre" USING btree ("costCentreId") WITH (deduplicate_items='true');


--
-- TOC entry 5365 (class 1259 OID 62017)
-- Name: idx_deliveryNoteDetails1Id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_deliveryNoteDetails1Id" ON public."tbl_DeliveryNoteDetails1" USING btree ("deliveryNoteDetails1Id") WITH (deduplicate_items='true');


--
-- TOC entry 5366 (class 1259 OID 62018)
-- Name: idx_deliveryNoteDetails1_productCode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_deliveryNoteDetails1_productCode" ON public."tbl_DeliveryNoteDetails1" USING btree ("productCode") WITH (deduplicate_items='true');


--
-- TOC entry 5353 (class 1259 OID 54362)
-- Name: idx_deliveryNoteMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_deliveryNoteMasterId" ON public."tbl_DeliveryNoteMaster" USING btree ("deliveryNoteMasterId") WITH (deduplicate_items='true');


--
-- TOC entry 5354 (class 1259 OID 54363)
-- Name: idx_deliveryNoteMaster_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_deliveryNoteMaster_voucherNo" ON public."tbl_DeliveryNoteMaster" USING btree ("voucherNo") WITH (deduplicate_items='true');


--
-- TOC entry 5077 (class 1259 OID 17008)
-- Name: idx_generalsettings_branchId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_generalsettings_branchId" ON public."tbl_GeneralSettings" USING btree ("branchId") WITH (deduplicate_items='true');


--
-- TOC entry 5078 (class 1259 OID 17009)
-- Name: idx_generalsettings_generalSettingsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_generalsettings_generalSettingsId" ON public."tbl_GeneralSettings" USING btree ("generalSettingsId") WITH (deduplicate_items='true');


--
-- TOC entry 5085 (class 1259 OID 17010)
-- Name: idx_market_MarketName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_market_MarketName" ON public."tbl_Market" USING btree ("MarketName") WITH (deduplicate_items='true');


--
-- TOC entry 5312 (class 1259 OID 37366)
-- Name: idx_proformaNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_proformaNo" ON public."tbl_proformaInvoiceMaster" USING btree ("proformaNo") WITH (deduplicate_items='true');


--
-- TOC entry 5313 (class 1259 OID 37367)
-- Name: idx_proforma_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_proforma_voucherNo" ON public."tbl_proformaInvoiceMaster" USING btree ("voucherNo") WITH (deduplicate_items='true');


--
-- TOC entry 5321 (class 1259 OID 37406)
-- Name: idx_proformadetails_proformaMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_proformadetails_proformaMasterId" ON public."tbl_proformaInvoiceDetails1" USING btree ("proformaMasterId") WITH (deduplicate_items='true');


--
-- TOC entry 5303 (class 1259 OID 34540)
-- Name: idx_quotationDetailsId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_quotationDetailsId" ON public."tbl_QuotationDetails" USING btree ("quotationDetailsId") WITH (deduplicate_items='true');


--
-- TOC entry 5294 (class 1259 OID 34451)
-- Name: idx_quotationMasterId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_quotationMasterId" ON public."tbl_QuotationMaster" USING btree ("quotationMasterId") WITH (deduplicate_items='true');


--
-- TOC entry 5295 (class 1259 OID 34453)
-- Name: idx_quotationMaster_quotationNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_quotationMaster_quotationNo" ON public."tbl_QuotationMaster" USING btree ("quotationNo") WITH (deduplicate_items='true');


--
-- TOC entry 5296 (class 1259 OID 34452)
-- Name: idx_quotationMaster_voucherNo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_quotationMaster_voucherNo" ON public."tbl_QuotationMaster" USING btree ("voucherNo") WITH (deduplicate_items='true');


--
-- TOC entry 5286 (class 1259 OID 27315)
-- Name: idx_tbl_Product_productName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_tbl_Product_productName" ON public."tbl_Product" USING btree ("productName") WITH (deduplicate_items='true');


--
-- TOC entry 5094 (class 1259 OID 17011)
-- Name: idx_unit_UnitName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_unit_UnitName" ON public."tbl_Unit" USING btree ("UnitName") WITH (deduplicate_items='true');


--
-- TOC entry 5095 (class 1259 OID 17012)
-- Name: idx_unit_unitId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_unit_unitId" ON public."tbl_Unit" USING btree ("unitId") WITH (deduplicate_items='true');


--
-- TOC entry 5203 (class 1259 OID 26227)
-- Name: idx_workLocation_workLocationId; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_workLocation_workLocationId" ON public."tbl_workLocation" USING btree ("workLocationId") WITH (deduplicate_items='true');


--
-- TOC entry 5204 (class 1259 OID 26228)
-- Name: idx_workLocation_workLocationName; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx_workLocation_workLocationName" ON public."tbl_workLocation" USING btree ("workLocationName") WITH (deduplicate_items='true');


--
-- TOC entry 5025 (class 1259 OID 17016)
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- TOC entry 5030 (class 1259 OID 17017)
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- TOC entry 5035 (class 1259 OID 17018)
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- TOC entry 5036 (class 1259 OID 17019)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- TOC entry 5039 (class 1259 OID 17020)
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- TOC entry 5397 (class 2606 OID 26278)
-- Name: tbl_Employee Employee_fk_DepartmentId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Employee"
    ADD CONSTRAINT "Employee_fk_DepartmentId" FOREIGN KEY ("DepartmentId") REFERENCES public."tbl_Department"("departmentId");


--
-- TOC entry 5398 (class 2606 OID 26298)
-- Name: tbl_Employee Employee_fk_areaId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Employee"
    ADD CONSTRAINT "Employee_fk_areaId" FOREIGN KEY ("areaId") REFERENCES public."tbl_Area"("AreaId");


--
-- TOC entry 5399 (class 2606 OID 26272)
-- Name: tbl_Employee Employee_fk_designationId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Employee"
    ADD CONSTRAINT "Employee_fk_designationId" FOREIGN KEY ("designationId") REFERENCES public."tbl_Designation"("designationId");


--
-- TOC entry 5400 (class 2606 OID 26304)
-- Name: tbl_Employee Employee_fk_marketId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Employee"
    ADD CONSTRAINT "Employee_fk_marketId" FOREIGN KEY ("marketId") REFERENCES public."tbl_Market"("MarketId");


--
-- TOC entry 5401 (class 2606 OID 26291)
-- Name: tbl_Employee Employee_fk_routeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Employee"
    ADD CONSTRAINT "Employee_fk_routeId" FOREIGN KEY ("routeId") REFERENCES public."tbl_Route"("RouteId");


--
-- TOC entry 5402 (class 2606 OID 26285)
-- Name: tbl_Employee Employee_fk_workLocationId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Employee"
    ADD CONSTRAINT "Employee_fk_workLocationId" FOREIGN KEY ("workLocationId") REFERENCES public."tbl_workLocation"("workLocationId");


--
-- TOC entry 5403 (class 2606 OID 26419)
-- Name: tbl_LedgerPosting LedgerPosting_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_LedgerPosting"
    ADD CONSTRAINT "LedgerPosting_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5404 (class 2606 OID 26394)
-- Name: tbl_LedgerPosting LedgerPosting_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_LedgerPosting"
    ADD CONSTRAINT "LedgerPosting_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5409 (class 2606 OID 26542)
-- Name: tbl_Partybalance Partybalance_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Partybalance"
    ADD CONSTRAINT "Partybalance_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5410 (class 2606 OID 26536)
-- Name: tbl_Partybalance Partybalance_fk_currecyConversionId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Partybalance"
    ADD CONSTRAINT "Partybalance_fk_currecyConversionId" FOREIGN KEY ("currencyConversionId") REFERENCES public."tbl_CurrencyConversion"("currencyConversionId");


--
-- TOC entry 5411 (class 2606 OID 26524)
-- Name: tbl_Partybalance Partybalance_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Partybalance"
    ADD CONSTRAINT "Partybalance_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5441 (class 2606 OID 34547)
-- Name: tbl_QuotationDetails QuotationDetails_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationDetails"
    ADD CONSTRAINT "QuotationDetails_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5442 (class 2606 OID 34541)
-- Name: tbl_QuotationDetails QuotationDetails_fk_quotationMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationDetails"
    ADD CONSTRAINT "QuotationDetails_fk_quotationMasterId" FOREIGN KEY ("quotationMasterId") REFERENCES public."tbl_QuotationMaster"("quotationMasterId");


--
-- TOC entry 5443 (class 2606 OID 34559)
-- Name: tbl_QuotationDetails QuotationDetails_fk_taxId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationDetails"
    ADD CONSTRAINT "QuotationDetails_fk_taxId" FOREIGN KEY ("taxId") REFERENCES public."tbl_TaxMaster"("taxId");


--
-- TOC entry 5444 (class 2606 OID 34553)
-- Name: tbl_QuotationDetails QuotationDetails_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationDetails"
    ADD CONSTRAINT "QuotationDetails_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5436 (class 2606 OID 34478)
-- Name: tbl_QuotationMaster QuotationMaster_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationMaster"
    ADD CONSTRAINT "QuotationMaster_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5437 (class 2606 OID 34466)
-- Name: tbl_QuotationMaster QuotationMaster_fk_currencyConversionId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationMaster"
    ADD CONSTRAINT "QuotationMaster_fk_currencyConversionId" FOREIGN KEY ("currencyConversionId") REFERENCES public."tbl_CurrencyConversion"("currencyConversionId");


--
-- TOC entry 5438 (class 2606 OID 34454)
-- Name: tbl_QuotationMaster QuotationMaster_fk_employeeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationMaster"
    ADD CONSTRAINT "QuotationMaster_fk_employeeId" FOREIGN KEY ("employeeId") REFERENCES public."tbl_Employee"("employeeId");


--
-- TOC entry 5439 (class 2606 OID 34472)
-- Name: tbl_QuotationMaster QuotationMaster_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationMaster"
    ADD CONSTRAINT "QuotationMaster_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5440 (class 2606 OID 34460)
-- Name: tbl_QuotationMaster QuotationMaster_fk_pricingLevelId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_QuotationMaster"
    ADD CONSTRAINT "QuotationMaster_fk_pricingLevelId" FOREIGN KEY ("pricingLevelId") REFERENCES public."tbl_PricingLevel"("PricingLevelId");


--
-- TOC entry 5423 (class 2606 OID 26713)
-- Name: tbl_ReceiptMaster ReceiptMaster_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ReceiptMaster"
    ADD CONSTRAINT "ReceiptMaster_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5424 (class 2606 OID 26707)
-- Name: tbl_ReceiptMaster ReceiptMaster_fk_employeeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ReceiptMaster"
    ADD CONSTRAINT "ReceiptMaster_fk_employeeId" FOREIGN KEY ("employeeId") REFERENCES public."tbl_Employee"("employeeId");


--
-- TOC entry 5425 (class 2606 OID 26701)
-- Name: tbl_ReceiptMaster ReceiptMaster_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ReceiptMaster"
    ADD CONSTRAINT "ReceiptMaster_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5426 (class 2606 OID 26742)
-- Name: tbl_Receiptdetails Receiptdetails_fk_currecyConversionId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Receiptdetails"
    ADD CONSTRAINT "Receiptdetails_fk_currecyConversionId" FOREIGN KEY ("currencyConversionId") REFERENCES public."tbl_CurrencyConversion"("currencyConversionId");


--
-- TOC entry 5427 (class 2606 OID 26736)
-- Name: tbl_Receiptdetails Receiptdetails_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Receiptdetails"
    ADD CONSTRAINT "Receiptdetails_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5428 (class 2606 OID 26730)
-- Name: tbl_Receiptdetails Receiptdetails_fk_receiptMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Receiptdetails"
    ADD CONSTRAINT "Receiptdetails_fk_receiptMasterId" FOREIGN KEY ("receiptMasterId") REFERENCES public."tbl_ReceiptMaster"("receiptMasterId");


--
-- TOC entry 5418 (class 2606 OID 26655)
-- Name: tbl_SalesReturnDetails1 SalesReturnDetails1_fk_GodownId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnDetails1"
    ADD CONSTRAINT "SalesReturnDetails1_fk_GodownId" FOREIGN KEY ("GodownId") REFERENCES public."tbl_Godown"("GodownId");


--
-- TOC entry 5419 (class 2606 OID 26625)
-- Name: tbl_SalesReturnDetails1 SalesReturnDetails1_fk_ReturnMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnDetails1"
    ADD CONSTRAINT "SalesReturnDetails1_fk_ReturnMasterId" FOREIGN KEY ("ReturnMasterId") REFERENCES public."tbl_SalesReturnMaster"("ReturnMasterId");


--
-- TOC entry 5420 (class 2606 OID 26631)
-- Name: tbl_SalesReturnDetails1 SalesReturnDetails1_fk_salesDetails1Id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnDetails1"
    ADD CONSTRAINT "SalesReturnDetails1_fk_salesDetails1Id" FOREIGN KEY ("salesDetails1Id") REFERENCES public."tbl_SalesDetails1"("salesDetails1Id");


--
-- TOC entry 5421 (class 2606 OID 26649)
-- Name: tbl_SalesReturnDetails1 SalesReturnDetails1_fk_taxId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnDetails1"
    ADD CONSTRAINT "SalesReturnDetails1_fk_taxId" FOREIGN KEY ("taxId") REFERENCES public."tbl_TaxMaster"("taxId");


--
-- TOC entry 5422 (class 2606 OID 26643)
-- Name: tbl_SalesReturnDetails1 SalesReturnDetails1_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnDetails1"
    ADD CONSTRAINT "SalesReturnDetails1_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5412 (class 2606 OID 45633)
-- Name: tbl_SalesReturnMaster SalesReturnMaster_fk_GodownId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnMaster"
    ADD CONSTRAINT "SalesReturnMaster_fk_GodownId" FOREIGN KEY ("GodownId") REFERENCES public."tbl_Godown"("GodownId");


--
-- TOC entry 5413 (class 2606 OID 26610)
-- Name: tbl_SalesReturnMaster SalesReturnMaster_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnMaster"
    ADD CONSTRAINT "SalesReturnMaster_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5414 (class 2606 OID 26604)
-- Name: tbl_SalesReturnMaster SalesReturnMaster_fk_employeeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnMaster"
    ADD CONSTRAINT "SalesReturnMaster_fk_employeeId" FOREIGN KEY ("employeeId") REFERENCES public."tbl_Employee"("employeeId");


--
-- TOC entry 5415 (class 2606 OID 26592)
-- Name: tbl_SalesReturnMaster SalesReturnMaster_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnMaster"
    ADD CONSTRAINT "SalesReturnMaster_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5416 (class 2606 OID 26598)
-- Name: tbl_SalesReturnMaster SalesReturnMaster_fk_pricingLevelId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnMaster"
    ADD CONSTRAINT "SalesReturnMaster_fk_pricingLevelId" FOREIGN KEY ("pricingLevelId") REFERENCES public."tbl_PricingLevel"("PricingLevelId");


--
-- TOC entry 5417 (class 2606 OID 26586)
-- Name: tbl_SalesReturnMaster SalesReturnMaster_fk_salesMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesReturnMaster"
    ADD CONSTRAINT "SalesReturnMaster_fk_salesMasterId" FOREIGN KEY ("salesMasterId") REFERENCES public."tbl_SalesMaster"("salesMasterId");


--
-- TOC entry 5405 (class 2606 OID 26500)
-- Name: tbl_StockPosting StockPosting_fk_VoucherUnitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_StockPosting"
    ADD CONSTRAINT "StockPosting_fk_VoucherUnitId" FOREIGN KEY ("VoucherUnitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5406 (class 2606 OID 26494)
-- Name: tbl_StockPosting StockPosting_fk_godownId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_StockPosting"
    ADD CONSTRAINT "StockPosting_fk_godownId" FOREIGN KEY ("godownId") REFERENCES public."tbl_Godown"("GodownId");


--
-- TOC entry 5407 (class 2606 OID 27396)
-- Name: tbl_StockPosting StockPosting_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_StockPosting"
    ADD CONSTRAINT "StockPosting_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5408 (class 2606 OID 26506)
-- Name: tbl_StockPosting StockPosting_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_StockPosting"
    ADD CONSTRAINT "StockPosting_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5396 (class 2606 OID 18309)
-- Name: tbl_SuffixPrefix SuffixPrefix_fk_yearId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SuffixPrefix"
    ADD CONSTRAINT "SuffixPrefix_fk_yearId" FOREIGN KEY ("yearId") REFERENCES public."tbl_FinancialYear"("yearId");


--
-- TOC entry 5451 (class 2606 OID 37413)
-- Name: tbl_proformaInvoiceDetails1 proformaInvoiceDetails1_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceDetails1"
    ADD CONSTRAINT "proformaInvoiceDetails1_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5452 (class 2606 OID 37407)
-- Name: tbl_proformaInvoiceDetails1 proformaInvoiceDetails1_fk_proformaMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceDetails1"
    ADD CONSTRAINT "proformaInvoiceDetails1_fk_proformaMasterId" FOREIGN KEY ("proformaMasterId") REFERENCES public."tbl_proformaInvoiceMaster"("proformaMasterId");


--
-- TOC entry 5453 (class 2606 OID 37425)
-- Name: tbl_proformaInvoiceDetails1 proformaInvoiceDetails1_fk_quotationDetailsId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceDetails1"
    ADD CONSTRAINT "proformaInvoiceDetails1_fk_quotationDetailsId" FOREIGN KEY ("quotationDetailsId") REFERENCES public."tbl_QuotationDetails"("quotationDetailsId");


--
-- TOC entry 5454 (class 2606 OID 37431)
-- Name: tbl_proformaInvoiceDetails1 proformaInvoiceDetails1_fk_taxId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceDetails1"
    ADD CONSTRAINT "proformaInvoiceDetails1_fk_taxId" FOREIGN KEY ("taxId") REFERENCES public."tbl_TaxMaster"("taxId");


--
-- TOC entry 5455 (class 2606 OID 37419)
-- Name: tbl_proformaInvoiceDetails1 proformaInvoiceDetails1_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceDetails1"
    ADD CONSTRAINT "proformaInvoiceDetails1_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5445 (class 2606 OID 37392)
-- Name: tbl_proformaInvoiceMaster proformaInvoiceMaster_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceMaster"
    ADD CONSTRAINT "proformaInvoiceMaster_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5446 (class 2606 OID 37386)
-- Name: tbl_proformaInvoiceMaster proformaInvoiceMaster_fk_currencyConversionId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceMaster"
    ADD CONSTRAINT "proformaInvoiceMaster_fk_currencyConversionId" FOREIGN KEY ("currencyConversionId") REFERENCES public."tbl_CurrencyConversion"("currencyConversionId");


--
-- TOC entry 5447 (class 2606 OID 37380)
-- Name: tbl_proformaInvoiceMaster proformaInvoiceMaster_fk_employeeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceMaster"
    ADD CONSTRAINT "proformaInvoiceMaster_fk_employeeId" FOREIGN KEY ("employeeId") REFERENCES public."tbl_Employee"("employeeId");


--
-- TOC entry 5448 (class 2606 OID 37368)
-- Name: tbl_proformaInvoiceMaster proformaInvoiceMaster_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceMaster"
    ADD CONSTRAINT "proformaInvoiceMaster_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5449 (class 2606 OID 37374)
-- Name: tbl_proformaInvoiceMaster proformaInvoiceMaster_fk_pricingLevelId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceMaster"
    ADD CONSTRAINT "proformaInvoiceMaster_fk_pricingLevelId" FOREIGN KEY ("pricingLevelId") REFERENCES public."tbl_PricingLevel"("PricingLevelId");


--
-- TOC entry 5450 (class 2606 OID 37529)
-- Name: tbl_proformaInvoiceMaster proformaInvoiceMaster_fk_quotationMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_proformaInvoiceMaster"
    ADD CONSTRAINT "proformaInvoiceMaster_fk_quotationMasterId" FOREIGN KEY ("quotationMasterId") REFERENCES public."tbl_QuotationMaster"("quotationMasterId");


--
-- TOC entry 5369 (class 2606 OID 17892)
-- Name: tbl_AccountLedger tbl_AccountLedger_fk_areaId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "tbl_AccountLedger_fk_areaId" FOREIGN KEY ("areaId") REFERENCES public."tbl_Area"("AreaId");


--
-- TOC entry 5370 (class 2606 OID 17848)
-- Name: tbl_AccountLedger tbl_AccountLedger_fk_currencyId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "tbl_AccountLedger_fk_currencyId" FOREIGN KEY ("currencyId") REFERENCES public."tbl_Currency"("currencyId");


--
-- TOC entry 5371 (class 2606 OID 17779)
-- Name: tbl_AccountLedger tbl_AccountLedger_fk_groupId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "tbl_AccountLedger_fk_groupId" FOREIGN KEY ("groupId") REFERENCES public."tbl_AccountGroup"("groupId");


--
-- TOC entry 5372 (class 2606 OID 17796)
-- Name: tbl_AccountLedger tbl_AccountLedger_fk_marketId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "tbl_AccountLedger_fk_marketId" FOREIGN KEY ("marketId") REFERENCES public."tbl_Market"("MarketId");


--
-- TOC entry 5373 (class 2606 OID 17841)
-- Name: tbl_AccountLedger tbl_AccountLedger_fk_pricingLevelId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "tbl_AccountLedger_fk_pricingLevelId" FOREIGN KEY ("pricingLevelId") REFERENCES public."tbl_PricingLevel"("PricingLevelId");


--
-- TOC entry 5374 (class 2606 OID 17887)
-- Name: tbl_AccountLedger tbl_AccountLedger_fk_routeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_AccountLedger"
    ADD CONSTRAINT "tbl_AccountLedger_fk_routeId" FOREIGN KEY ("routeId") REFERENCES public."tbl_Route"("RouteId");


--
-- TOC entry 5381 (class 2606 OID 18094)
-- Name: tbl_BOM tbl_BOM_fk_UnitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_BOM"
    ADD CONSTRAINT "tbl_BOM_fk_UnitId" FOREIGN KEY ("UnitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5382 (class 2606 OID 34591)
-- Name: tbl_BOM tbl_BOM_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_BOM"
    ADD CONSTRAINT "tbl_BOM_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5383 (class 2606 OID 18115)
-- Name: tbl_CurrencyConversion tbl_CurrencyConversion_fk_currencyId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_CurrencyConversion"
    ADD CONSTRAINT "tbl_CurrencyConversion_fk_currencyId" FOREIGN KEY ("currencyId") REFERENCES public."tbl_Currency"("currencyId");


--
-- TOC entry 5477 (class 2606 OID 62044)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_fk_GodownId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_fk_GodownId" FOREIGN KEY ("GodownId") REFERENCES public."tbl_Godown"("GodownId");


--
-- TOC entry 5478 (class 2606 OID 62019)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_fk_deliveryNoteMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_fk_deliveryNoteMasterId" FOREIGN KEY ("deliveryNoteMasterId") REFERENCES public."tbl_DeliveryNoteMaster"("deliveryNoteMasterId");


--
-- TOC entry 5479 (class 2606 OID 62050)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_fk_orderDetails1Id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_fk_orderDetails1Id" FOREIGN KEY ("orderDetails1Id") REFERENCES public."tbl_SalesOrderdetails1"("orderDetails1Id");


--
-- TOC entry 5480 (class 2606 OID 62025)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5481 (class 2606 OID 62062)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_fk_proformaDetails1Id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_fk_proformaDetails1Id" FOREIGN KEY ("proformaDetails1Id") REFERENCES public."tbl_proformaInvoiceDetails1"("proformaDetails1Id");


--
-- TOC entry 5482 (class 2606 OID 62056)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_fk_quotationDetailsId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_fk_quotationDetailsId" FOREIGN KEY ("quotationDetailsId") REFERENCES public."tbl_QuotationDetails"("quotationDetailsId");


--
-- TOC entry 5483 (class 2606 OID 62038)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_fk_taxId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_fk_taxId" FOREIGN KEY ("taxId") REFERENCES public."tbl_TaxMaster"("taxId");


--
-- TOC entry 5484 (class 2606 OID 62031)
-- Name: tbl_DeliveryNoteDetails1 tbl_DeliveryNoteDetails1_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteDetails1"
    ADD CONSTRAINT "tbl_DeliveryNoteDetails1_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5469 (class 2606 OID 54370)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5470 (class 2606 OID 54388)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_fk_currencyConversionId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_fk_currencyConversionId" FOREIGN KEY ("currencyConversionId") REFERENCES public."tbl_CurrencyConversion"("currencyConversionId");


--
-- TOC entry 5471 (class 2606 OID 54382)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_fk_employeeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_fk_employeeId" FOREIGN KEY ("employeeId") REFERENCES public."tbl_Employee"("employeeId");


--
-- TOC entry 5472 (class 2606 OID 54364)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5473 (class 2606 OID 54394)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_fk_orderMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_fk_orderMasterId" FOREIGN KEY ("orderMasterId") REFERENCES public."tbl_SalesOrderMaster"("orderMasterId");


--
-- TOC entry 5474 (class 2606 OID 54376)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_fk_pricingLevelId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_fk_pricingLevelId" FOREIGN KEY ("pricingLevelId") REFERENCES public."tbl_PricingLevel"("PricingLevelId");


--
-- TOC entry 5475 (class 2606 OID 54411)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_fk_proformaMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_fk_proformaMasterId" FOREIGN KEY ("proformaMasterId") REFERENCES public."tbl_proformaInvoiceMaster"("proformaMasterId");


--
-- TOC entry 5476 (class 2606 OID 54406)
-- Name: tbl_DeliveryNoteMaster tbl_DeliveryNoteMaster_fk_quotationMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_DeliveryNoteMaster"
    ADD CONSTRAINT "tbl_DeliveryNoteMaster_fk_quotationMasterId" FOREIGN KEY ("quotationMasterId") REFERENCES public."tbl_QuotationMaster"("quotationMasterId");


--
-- TOC entry 5380 (class 2606 OID 17990)
-- Name: tbl_FinancialYearMonth tbl_FinancialYearMonth_fk_yearId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_FinancialYearMonth"
    ADD CONSTRAINT "tbl_FinancialYearMonth_fk_yearId" FOREIGN KEY ("yearId") REFERENCES public."tbl_FinancialYear"("yearId");


--
-- TOC entry 5375 (class 2606 OID 17552)
-- Name: tbl_ProductSalesPrice tbl_ProductSalesPrice_fk_pricingLevelId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ProductSalesPrice"
    ADD CONSTRAINT "tbl_ProductSalesPrice_fk_pricingLevelId" FOREIGN KEY ("PricingLevelId") REFERENCES public."tbl_PricingLevel"("PricingLevelId");


--
-- TOC entry 5376 (class 2606 OID 34580)
-- Name: tbl_ProductSalesPrice tbl_ProductSalesPrice_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ProductSalesPrice"
    ADD CONSTRAINT "tbl_ProductSalesPrice_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5377 (class 2606 OID 17546)
-- Name: tbl_ProductSalesPrice tbl_ProductSalesPrice_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ProductSalesPrice"
    ADD CONSTRAINT "tbl_ProductSalesPrice_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5429 (class 2606 OID 27316)
-- Name: tbl_Product tbl_Product_fk_brandId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product"
    ADD CONSTRAINT "tbl_Product_fk_brandId" FOREIGN KEY ("brandId") REFERENCES public."tbl_Brand"("brandId");


--
-- TOC entry 5430 (class 2606 OID 27321)
-- Name: tbl_Product tbl_Product_fk_group1Id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product"
    ADD CONSTRAINT "tbl_Product_fk_group1Id" FOREIGN KEY ("group1Id") REFERENCES public."tbl_ProductGroup"("groupId");


--
-- TOC entry 5431 (class 2606 OID 27326)
-- Name: tbl_Product tbl_Product_fk_group2Id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product"
    ADD CONSTRAINT "tbl_Product_fk_group2Id" FOREIGN KEY ("group2Id") REFERENCES public."tbl_ProductGroup"("groupId");


--
-- TOC entry 5432 (class 2606 OID 27331)
-- Name: tbl_Product tbl_Product_fk_group3Id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product"
    ADD CONSTRAINT "tbl_Product_fk_group3Id" FOREIGN KEY ("group3Id") REFERENCES public."tbl_ProductGroup"("groupId");


--
-- TOC entry 5433 (class 2606 OID 27336)
-- Name: tbl_Product tbl_Product_fk_group4Id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product"
    ADD CONSTRAINT "tbl_Product_fk_group4Id" FOREIGN KEY ("group4Id") REFERENCES public."tbl_ProductGroup"("groupId");


--
-- TOC entry 5434 (class 2606 OID 27341)
-- Name: tbl_Product tbl_Product_fk_groupCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product"
    ADD CONSTRAINT "tbl_Product_fk_groupCode" FOREIGN KEY ("groupCode") REFERENCES public."tbl_ProductMainGroup"("groupCode");


--
-- TOC entry 5435 (class 2606 OID 27346)
-- Name: tbl_Product tbl_Product_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_Product"
    ADD CONSTRAINT "tbl_Product_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5391 (class 2606 OID 18221)
-- Name: tbl_SalesDetails1 tbl_SalesDetails1_fk_GodownId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesDetails1"
    ADD CONSTRAINT "tbl_SalesDetails1_fk_GodownId" FOREIGN KEY ("GodownId") REFERENCES public."tbl_Godown"("GodownId");


--
-- TOC entry 5392 (class 2606 OID 27428)
-- Name: tbl_SalesDetails1 tbl_SalesDetails1_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesDetails1"
    ADD CONSTRAINT "tbl_SalesDetails1_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5393 (class 2606 OID 18203)
-- Name: tbl_SalesDetails1 tbl_SalesDetails1_fk_salesMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesDetails1"
    ADD CONSTRAINT "tbl_SalesDetails1_fk_salesMasterId" FOREIGN KEY ("salesMasterId") REFERENCES public."tbl_SalesMaster"("salesMasterId");


--
-- TOC entry 5394 (class 2606 OID 18215)
-- Name: tbl_SalesDetails1 tbl_SalesDetails1_fk_taxId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesDetails1"
    ADD CONSTRAINT "tbl_SalesDetails1_fk_taxId" FOREIGN KEY ("taxId") REFERENCES public."tbl_TaxMaster"("taxId");


--
-- TOC entry 5395 (class 2606 OID 18209)
-- Name: tbl_SalesDetails1 tbl_SalesDetails1_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesDetails1"
    ADD CONSTRAINT "tbl_SalesDetails1_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5385 (class 2606 OID 18177)
-- Name: tbl_SalesMaster tbl_SalesMaster_fk_GodownId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesMaster"
    ADD CONSTRAINT "tbl_SalesMaster_fk_GodownId" FOREIGN KEY ("GodownId") REFERENCES public."tbl_Godown"("GodownId");


--
-- TOC entry 5386 (class 2606 OID 18183)
-- Name: tbl_SalesMaster tbl_SalesMaster_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesMaster"
    ADD CONSTRAINT "tbl_SalesMaster_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5387 (class 2606 OID 18171)
-- Name: tbl_SalesMaster tbl_SalesMaster_fk_currecyConversionId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesMaster"
    ADD CONSTRAINT "tbl_SalesMaster_fk_currecyConversionId" FOREIGN KEY ("currencyConversionId") REFERENCES public."tbl_CurrencyConversion"("currencyConversionId");


--
-- TOC entry 5388 (class 2606 OID 26376)
-- Name: tbl_SalesMaster tbl_SalesMaster_fk_employeeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesMaster"
    ADD CONSTRAINT "tbl_SalesMaster_fk_employeeId" FOREIGN KEY ("employeeId") REFERENCES public."tbl_Employee"("employeeId");


--
-- TOC entry 5389 (class 2606 OID 18159)
-- Name: tbl_SalesMaster tbl_SalesMaster_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesMaster"
    ADD CONSTRAINT "tbl_SalesMaster_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5390 (class 2606 OID 18165)
-- Name: tbl_SalesMaster tbl_SalesMaster_fk_pricingLevelId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesMaster"
    ADD CONSTRAINT "tbl_SalesMaster_fk_pricingLevelId" FOREIGN KEY ("pricingLevelId") REFERENCES public."tbl_PricingLevel"("PricingLevelId");


--
-- TOC entry 5456 (class 2606 OID 53972)
-- Name: tbl_SalesOrderMaster tbl_SalesOrderMaster_fk_costCentreId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderMaster"
    ADD CONSTRAINT "tbl_SalesOrderMaster_fk_costCentreId" FOREIGN KEY ("costCentreId") REFERENCES public."tbl_CostCentre"("costCentreId");


--
-- TOC entry 5457 (class 2606 OID 53978)
-- Name: tbl_SalesOrderMaster tbl_SalesOrderMaster_fk_currencyConversionId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderMaster"
    ADD CONSTRAINT "tbl_SalesOrderMaster_fk_currencyConversionId" FOREIGN KEY ("currencyConversionId") REFERENCES public."tbl_CurrencyConversion"("currencyConversionId");


--
-- TOC entry 5458 (class 2606 OID 53966)
-- Name: tbl_SalesOrderMaster tbl_SalesOrderMaster_fk_employeeId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderMaster"
    ADD CONSTRAINT "tbl_SalesOrderMaster_fk_employeeId" FOREIGN KEY ("employeeId") REFERENCES public."tbl_Employee"("employeeId");


--
-- TOC entry 5459 (class 2606 OID 53954)
-- Name: tbl_SalesOrderMaster tbl_SalesOrderMaster_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderMaster"
    ADD CONSTRAINT "tbl_SalesOrderMaster_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5460 (class 2606 OID 53960)
-- Name: tbl_SalesOrderMaster tbl_SalesOrderMaster_fk_pricingLevelId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderMaster"
    ADD CONSTRAINT "tbl_SalesOrderMaster_fk_pricingLevelId" FOREIGN KEY ("pricingLevelId") REFERENCES public."tbl_PricingLevel"("PricingLevelId");


--
-- TOC entry 5461 (class 2606 OID 53991)
-- Name: tbl_SalesOrderMaster tbl_SalesOrderMaster_fk_proformaMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderMaster"
    ADD CONSTRAINT "tbl_SalesOrderMaster_fk_proformaMasterId" FOREIGN KEY ("proformaMasterId") REFERENCES public."tbl_proformaInvoiceMaster"("proformaMasterId");


--
-- TOC entry 5462 (class 2606 OID 53984)
-- Name: tbl_SalesOrderMaster tbl_SalesOrderMaster_fk_quotationMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderMaster"
    ADD CONSTRAINT "tbl_SalesOrderMaster_fk_quotationMasterId" FOREIGN KEY ("quotationMasterId") REFERENCES public."tbl_QuotationMaster"("quotationMasterId");


--
-- TOC entry 5463 (class 2606 OID 54005)
-- Name: tbl_SalesOrderdetails1 tbl_SalesOrderdetails1_fk_orderMasterId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderdetails1"
    ADD CONSTRAINT "tbl_SalesOrderdetails1_fk_orderMasterId" FOREIGN KEY ("orderMasterId") REFERENCES public."tbl_SalesOrderMaster"("orderMasterId");


--
-- TOC entry 5464 (class 2606 OID 54024)
-- Name: tbl_SalesOrderdetails1 tbl_SalesOrderdetails1_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderdetails1"
    ADD CONSTRAINT "tbl_SalesOrderdetails1_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5465 (class 2606 OID 54018)
-- Name: tbl_SalesOrderdetails1 tbl_SalesOrderdetails1_fk_proformaDetails1Id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderdetails1"
    ADD CONSTRAINT "tbl_SalesOrderdetails1_fk_proformaDetails1Id" FOREIGN KEY ("proformaDetails1Id") REFERENCES public."tbl_proformaInvoiceDetails1"("proformaDetails1Id");


--
-- TOC entry 5466 (class 2606 OID 54012)
-- Name: tbl_SalesOrderdetails1 tbl_SalesOrderdetails1_fk_quotationDetailsId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderdetails1"
    ADD CONSTRAINT "tbl_SalesOrderdetails1_fk_quotationDetailsId" FOREIGN KEY ("quotationDetailsId") REFERENCES public."tbl_QuotationDetails"("quotationDetailsId");


--
-- TOC entry 5467 (class 2606 OID 54036)
-- Name: tbl_SalesOrderdetails1 tbl_SalesOrderdetails1_fk_taxId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderdetails1"
    ADD CONSTRAINT "tbl_SalesOrderdetails1_fk_taxId" FOREIGN KEY ("taxId") REFERENCES public."tbl_TaxMaster"("taxId");


--
-- TOC entry 5468 (class 2606 OID 54030)
-- Name: tbl_SalesOrderdetails1 tbl_SalesOrderdetails1_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_SalesOrderdetails1"
    ADD CONSTRAINT "tbl_SalesOrderdetails1_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


--
-- TOC entry 5384 (class 2606 OID 18133)
-- Name: tbl_ShippingAddress tbl_ShippingAddress_fk_ledgerId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_ShippingAddress"
    ADD CONSTRAINT "tbl_ShippingAddress_fk_ledgerId" FOREIGN KEY ("ledgerId") REFERENCES public."tbl_AccountLedger"("ledgerId");


--
-- TOC entry 5378 (class 2606 OID 34586)
-- Name: tbl_UnitConversion tbl_UnitConversion_fk_productCode; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_UnitConversion"
    ADD CONSTRAINT "tbl_UnitConversion_fk_productCode" FOREIGN KEY ("productCode") REFERENCES public."tbl_Product"("productCode");


--
-- TOC entry 5379 (class 2606 OID 17571)
-- Name: tbl_UnitConversion tbl_UnitConversion_fk_unitId; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."tbl_UnitConversion"
    ADD CONSTRAINT "tbl_UnitConversion_fk_unitId" FOREIGN KEY ("unitId") REFERENCES public."tbl_Unit"("unitId");


-- Completed on 2025-11-04 16:54:11

--
-- PostgreSQL database dump complete
--

\unrestrict gau8v1AJfLTv3WPYfV8p4RTx0PCh6IndaCqc8BYDwMxIL54vWvM2lwwuBit4A8B

